<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BracketGenerator
{
    public function generate(Tournament $tournament): void
    {
        DB::transaction(function () use ($tournament) {
            $registrations = $tournament->registrations()->where('status', 'registered')->get();
            $playerCount = $registrations->count();
            
            if ($playerCount < $tournament->min_players) {
                throw new \Exception("Not enough players to generate bracket. Minimum: {$tournament->min_players}, Registered: {$playerCount}");
            }

            // Calculate nearest power of 2
            $bracketSize = $this->calculateNearestPowerOfTwo($playerCount);
            $byeCount = $bracketSize - $playerCount;

            // Shuffle players for random bracket positioning
            $shuffledPlayers = $registrations->shuffle();
            
            // Assign bracket positions
            $this->assignBracketPositions($shuffledPlayers, $bracketSize, $byeCount);

            // Generate matches
            $this->generateMatches($tournament, $bracketSize);

            // Update tournament status
            $tournament->update([
                'status' => 'in_progress',
                'total_pool' => $tournament->calculateTotalPool(),
            ]);
        });
    }

    private function calculateNearestPowerOfTwo(int $number): int
    {
        $power = 4; // Minimum bracket size
        while ($power < $number) {
            $power *= 2;
        }
        return $power;
    }

    private function assignBracketPositions(Collection $players, int $bracketSize, int $byeCount): void
    {
        // Randomly select players who receive byes
        $byePlayers = $players->random($byeCount);
        
        $position = 1;
        foreach ($players as $registration) {
            $receivedBye = $byePlayers->contains($registration);
            $registration->update([
                'bracket_position' => $position,
                'received_bye' => $receivedBye,
            ]);
            $position++;
        }
    }

    private function generateMatches(Tournament $tournament, int $bracketSize): void
    {
        $rounds = (int) log($bracketSize, 2);
        $registrations = $tournament->registrations()->where('status', 'registered')->get()->sortBy('bracket_position');
        
        $currentRoundPlayers = $registrations->pluck('id')->toArray();
        
        for ($round = 1; $round <= $rounds; $round++) {
            $matchCount = count($currentRoundPlayers) / 2;
            
            for ($match = 1; $match <= $matchCount; $match++) {
                $player1Index = ($match - 1) * 2;
                $player2Index = $player1Index + 1;
                
                $player1Id = $currentRoundPlayers[$player1Index] ?? null;
                $player2Id = $currentRoundPlayers[$player2Index] ?? null;
                
                // If player has a bye and it's round 1, they advance automatically
                if ($round === 1) {
                    $reg1 = $registrations->firstWhere('id', $player1Id);
                    $reg2 = $registrations->firstWhere('id', $player2Id);
                    
                    if ($reg1 && $reg1->received_bye) {
                        $player2Id = null; // Player 1 gets a bye
                    } elseif ($reg2 && $reg2->received_bye) {
                        $player1Id = null; // Player 2 gets a bye
                    }
                }

                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $round,
                    'match_number' => $match,
                    'player1_id' => $player1Id ? $registrations->firstWhere('id', $player1Id)->user_id : null,
                    'player2_id' => $player2Id ? $registrations->firstWhere('id', $player2Id)->user_id : null,
                    'status' => 'pending',
                    'scheduled_at' => $tournament->starts_at->addMinutes(($round - 1) * 30),
                ]);
            }

            // Prepare for next round (will be filled as matches complete)
            $currentRoundPlayers = array_fill(0, $matchCount, null);
        }
    }

    public function advanceWinner(TournamentMatch $match, int $winnerUserId): void
    {
        DB::transaction(function () use ($match, $winnerUserId) {
            $match->update([
                'winner_id' => $winnerUserId,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->resolveBets($match, $winnerUserId);
            $this->awardMatchPoints($winnerUserId);

            // Find next round match for the winner
            $nextRound = $match->round_number + 1;
            
            if ($match->bracket_tier === 'grand_finals') {
                $this->finalizeTournament($match->tournament, $winnerUserId);
                return;
            }

            if ($match->bracket_tier === 'losers_bracket') {
                $this->advanceLosersBracketWinner($match, $winnerUserId, $nextRound);
            } else {
                $this->advanceWinnersBracketWinner($match, $winnerUserId, $nextRound);
            }

            // Eliminate loser or route to losers bracket
            $loserId = $match->player1_id === $winnerUserId ? $match->player2_id : $match->player1_id;
            if ($loserId) {
                if ($match->tournament->bracket_type === 'double_elimination' && $match->bracket_tier === 'winners_bracket') {
                    $this->moveToLosersBracket($match, $loserId);
                } else {
                    $this->eliminatePlayer($match->tournament_id, $loserId);
                }
            }
        });
    }

    private function resolveBets(TournamentMatch $match, int $winnerUserId): void
    {
        \App\Models\TournamentBet::where('tournament_match_id', $match->id)
            ->where('status', 'pending')
            ->chunkById(100, function ($bets) use ($winnerUserId) {
                foreach ($bets as $bet) {
                    if ($bet->predicted_winner_id === $winnerUserId) {
                        $winnings = $bet->amount * $bet->odds;
                        $bet->update(['status' => 'won']);
                        $bet->user->increment('balance', $winnings);
                        \App\Models\Transaction::create([
                            'user_id' => $bet->user_id,
                            'amount' => $winnings,
                            'type' => 'bet_won',
                            'status' => 'completed',
                            'description' => "Won bet on match {$bet->tournament_match_id}",
                        ]);
                    } else {
                        $bet->update(['status' => 'lost']);
                    }
                }
            });
    }

    private function awardMatchPoints(int $userId): void
    {
        $leaderboard = \App\Models\TournamentLeaderboard::firstOrCreate(
            ['user_id' => $userId, 'season_id' => 1],
            ['points' => 0, 'matches_won' => 0, 'tournaments_won' => 0, 'total_earnings' => 0]
        );
        $leaderboard->increment('points', 10);
        $leaderboard->increment('matches_won');
    }

    private function advanceWinnersBracketWinner(TournamentMatch $match, int $winnerUserId, int $nextRound): void
    {
        $nextMatchNumber = ceil($match->match_number / 2);
        
        $nextMatch = TournamentMatch::where('tournament_id', $match->tournament_id)
            ->where('bracket_tier', 'winners_bracket')
            ->where('round_number', $nextRound)
            ->where('match_number', $nextMatchNumber)
            ->first();

        if ($nextMatch) {
            if ($match->match_number % 2 === 1) {
                $nextMatch->update(['player1_id' => $winnerUserId]);
            } else {
                $nextMatch->update(['player2_id' => $winnerUserId]);
            }
            $this->checkAndScheduleMatch($nextMatch);
        } else {
            // Reached the end of winners bracket. Wait for losers bracket, or finalize if single elimination.
            if ($match->tournament->bracket_type === 'double_elimination') {
                $this->createGrandFinalsMatch($match->tournament_id, $winnerUserId, 'player1_id');
            } else {
                $this->finalizeTournament($match->tournament, $winnerUserId);
            }
        }
    }

    private function advanceLosersBracketWinner(TournamentMatch $match, int $winnerUserId, int $nextRound): void
    {
        $openMatch = TournamentMatch::where('tournament_id', $match->tournament_id)
            ->where('bracket_tier', 'losers_bracket')
            ->where('round_number', $nextRound)
            ->where(function($q) {
                $q->whereNull('player1_id')->orWhereNull('player2_id');
            })->first();

        if ($openMatch) {
            if (!$openMatch->player1_id) {
                $openMatch->update(['player1_id' => $winnerUserId]);
            } else {
                $openMatch->update(['player2_id' => $winnerUserId]);
            }
            $this->checkAndScheduleMatch($openMatch);
        } else {
            // Reached the end of losers bracket
            $this->createGrandFinalsMatch($match->tournament_id, $winnerUserId, 'player2_id');
        }
    }

    private function createGrandFinalsMatch(int $tournamentId, int $userId, string $playerSlot): void
    {
        $grandFinals = TournamentMatch::firstOrCreate([
            'tournament_id' => $tournamentId,
            'bracket_tier' => 'grand_finals',
            'round_number' => 1,
            'match_number' => 1,
        ]);
        $grandFinals->update([$playerSlot => $userId]);
        $this->checkAndScheduleMatch($grandFinals);
    }

    private function moveToLosersBracket(TournamentMatch $match, int $loserId): void
    {
        $losersRound = $match->round_number;
        
        $openMatch = TournamentMatch::where('tournament_id', $match->tournament_id)
            ->where('bracket_tier', 'losers_bracket')
            ->where('round_number', $losersRound)
            ->where(function($q) {
                $q->whereNull('player1_id')->orWhereNull('player2_id');
            })->first();

        if ($openMatch) {
            if (!$openMatch->player1_id) {
                $openMatch->update(['player1_id' => $loserId]);
            } else {
                $openMatch->update(['player2_id' => $loserId]);
            }
            $this->checkAndScheduleMatch($openMatch);
        } else {
            $newMatchNumber = TournamentMatch::where('tournament_id', $match->tournament_id)
                ->where('bracket_tier', 'losers_bracket')
                ->where('round_number', $losersRound)
                ->count() + 1;

            TournamentMatch::create([
                'tournament_id' => $match->tournament_id,
                'bracket_tier' => 'losers_bracket',
                'round_number' => $losersRound,
                'match_number' => $newMatchNumber,
                'player1_id' => $loserId,
                'player2_id' => null,
                'status' => 'pending',
            ]);
        }
    }

    private function checkAndScheduleMatch(TournamentMatch $match): void
    {
        if ($match->player1_id && $match->player2_id && $match->status === 'pending') {
            $match->update([
                'status' => 'scheduled',
                'scheduled_at' => now()->addMinutes(5),
            ]);
            $this->startReadyCheck($match);
        }
    }

    private function eliminatePlayer(int $tournamentId, int $userId): void
    {
        $registration = TournamentRegistration::where('tournament_id', $tournamentId)
            ->where('user_id', $userId)
            ->first();
        
        if ($registration) {
            $registration->update(['status' => 'eliminated']);
        }
    }

    private function startReadyCheck(TournamentMatch $match): void
    {
        $match->update([
            'ready_check_started_at' => now(),
            'player1_ready' => false,
            'player2_ready' => false,
        ]);

        // Send WebPush notification to both players
        $notification = new \App\Notifications\TournamentReadyCheckNotification($match);
        if ($match->player1_id) {
            \App\Models\User::find($match->player1_id)?->notify($notification);
        }
        if ($match->player2_id) {
            \App\Models\User::find($match->player2_id)?->notify($notification);
        }

        // Broadcast ready check event via WebSocket
        broadcast(new \App\Events\TournamentMatchReady($match));
    }

    private function finalizeTournament(Tournament $tournament, int $winnerUserId): void
    {
        // Update winner status
        $winnerRegistration = TournamentRegistration::where('tournament_id', $tournament->id)
            ->where('user_id', $winnerUserId)
            ->first();
        
        if ($winnerRegistration) {
            $winnerRegistration->update([
                'status' => 'winner',
                'final_rank' => 1,
            ]);

            $leaderboard = \App\Models\TournamentLeaderboard::firstOrCreate(
                ['user_id' => $winnerUserId, 'season_id' => 1],
                ['points' => 0, 'matches_won' => 0, 'tournaments_won' => 0, 'total_earnings' => 0]
            );
            $leaderboard->increment('points', 50);
            $leaderboard->increment('tournaments_won');
        }

        // Calculate and distribute prizes
        $this->distributePrizes($tournament);

        // Update tournament status
        $tournament->update([
            'status' => 'completed',
            'ends_at' => now(),
        ]);
    }

    private function distributePrizes(Tournament $tournament): void
    {
        $prizePool = $tournament->calculatePrizePool();
        
        // Get final rankings
        $rankings = TournamentRegistration::where('tournament_id', $tournament->id)
            ->whereNotNull('final_rank')
            ->orderBy('final_rank')
            ->get();

        foreach ($rankings as $registration) {
            $prizeAmount = 0;
            $percentage = 0;
            $rankLabel = '';

            switch ($registration->final_rank) {
                case 1:
                    $percentage = $tournament->winner_prize_percentage;
                    $rankLabel = 'Winner';
                    break;
                case 2:
                    $percentage = $tournament->runner_up_prize_percentage;
                    $rankLabel = 'Runner-up';
                    break;
                case 3:
                case 4:
                    if ($tournament->include_semifinalist_prizes && $tournament->semifinalist_prize_percentage) {
                        $percentage = $tournament->semifinalist_prize_percentage / 2; // Split between 3rd and 4th
                        $rankLabel = $registration->final_rank === 3 ? '3rd Place' : '4th Place';
                    }
                    break;
            }

            if ($percentage > 0) {
                $prizeAmount = $prizePool * ($percentage / 100);
                
                // Create transaction for prize
                $transaction = \App\Models\Transaction::create([
                    'user_id' => $registration->user_id,
                    'amount' => $prizeAmount,
                    'type' => 'tournament_prize',
                    'status' => 'completed',
                    'description' => "Prize for {$tournament->title} - {$rankLabel}",
                ]);

                // Update user balance
                $registration->user->increment('balance', $prizeAmount);

                // Create prize distribution record
                TournamentPrizeDistribution::create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $registration->user_id,
                    'tournament_registration_id' => $registration->id,
                    'rank' => $registration->final_rank,
                    'rank_label' => $rankLabel,
                    'prize_amount' => $prizeAmount,
                    'percentage_of_pool' => $percentage,
                    'transaction_id' => $transaction->id,
                    'distributed_at' => now(),
                ]);

                $registration->update(['prize_won' => $prizeAmount]);
            }
        }

        // Calculate house profit
        $totalDistributed = TournamentPrizeDistribution::where('tournament_id', $tournament->id)
            ->sum('prize_amount');
        
        $platformCommission = $tournament->calculatePlatformCommission();
        $houseProfit = $platformCommission + ($tournament->total_pool - $totalDistributed);

        $tournament->update([
            'total_distributed_prizes' => $totalDistributed,
            'house_profit' => $houseProfit,
        ]);
    }

    public function handleForfeit(TournamentMatch $match, int $forfeitingUserId): void
    {
        $winnerId = $match->player1_id === $forfeitingUserId ? $match->player2_id : $match->player1_id;
        
        if ($winnerId) {
            $match->update([
                'winner_id' => $winnerId,
                'status' => 'completed',
                'completed_at' => now(),
                'result_type' => 'forfeit',
            ]);

            // Update forfeiting player status
            TournamentRegistration::where('tournament_id', $match->tournament_id)
                ->where('user_id', $forfeitingUserId)
                ->update(['status' => 'forfeited']);

            $this->advanceWinner($match, $winnerId);
        }
    }

    public function handleDoubleDisconnect(TournamentMatch $match): void
    {
        // Compare performance metrics
        $player1Score = $this->extractScore($match->player1_metrics);
        $player2Score = $this->extractScore($match->player2_metrics);

        if ($player1Score > $player2Score) {
            $winnerId = $match->player1_id;
        } elseif ($player2Score > $player1Score) {
            $winnerId = $match->player2_id;
        } else {
            // Random tiebreaker
            $winnerId = rand(0, 1) === 0 ? $match->player1_id : $match->player2_id;
            $match->result_type = 'random';
        }

        $match->update([
            'winner_id' => $winnerId,
            'status' => 'completed',
            'completed_at' => now(),
            'result_type' => $match->result_type ?? 'double_disconnect',
        ]);

        $this->advanceWinner($match, $winnerId);
    }

    private function extractScore(?array $metrics): float
    {
        if (!$metrics) {
            return 0;
        }
        
        // Extract the primary score metric (could be high_score, time_survived, etc.)
        return $metrics['score'] ?? $metrics['high_score'] ?? $metrics['time_survived'] ?? 0;
    }
}
