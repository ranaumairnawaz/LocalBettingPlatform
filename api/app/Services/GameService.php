<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\GameMatchPlayer;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameService
{
    /**
     * Join or create a match for a specific game type.
     */
    public function joinMatch(User $user, string $gameType, float $betAmount, int $maxPlayers = 2)
    {
        if ($user->balance < $betAmount) {
            throw new \Exception('Insufficient balance.');
        }

        return DB::transaction(function () use ($user, $gameType, $betAmount, $maxPlayers) {
            // Check if already in a match
            $existing = GameMatchPlayer::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereHas('match', function ($q) use ($gameType) {
                    $q->where('game_type', $gameType)
                      ->whereIn('status', ['waiting', 'playing']);
                })->first();

            if ($existing) {
                if ($existing->match->status === 'playing') {
                    $this->handleForfeit($existing->match, $user);
                } else {
                    return $existing->match;
                }
            }

            // Retrieve dynamic bet limits from database settings
            $minPercent = (float) SystemSetting::get('matching_min_percent', 50);
            $maxPercent = (float) SystemSetting::get('matching_max_percent', 200);
            $minBet = $betAmount * ($minPercent / 100);
            $maxBetLimit = $betAmount * ($maxPercent / 100);

            // Find an available match with compatible bet amount (dynamic matching range)
            if ($gameType === 'pool_snooker') {
                $match = GameMatch::where('game_type', $gameType)
                    ->where('status', 'waiting')
                    ->withCount('players')
                    ->havingBetween('players_count', [1, 4])
                    ->whereHas('players', function($q) use ($minBet, $maxBetLimit) {
                        $q->whereBetween('bet_amount', [$minBet, $maxBetLimit]);
                    })
                    ->oldest()
                    ->lockForUpdate()
                    ->first();
            } else {
                $match = GameMatch::where('game_type', $gameType)
                    ->where('status', 'waiting')
                    ->withCount('players')
                    ->having('players_count', '<', $maxPlayers)
                    ->whereHas('players', function($q) use ($minBet, $maxBetLimit) {
                        $q->whereBetween('bet_amount', [$minBet, $maxBetLimit]);
                    })
                    ->oldest()
                    ->lockForUpdate()
                    ->first();
            }

            if (!$match) {
                // Create new match
                $match = GameMatch::create([
                    'room_code' => strtoupper(Str::random(8)),
                    'game_type' => $gameType,
                    'status' => 'waiting',
                    'total_pot' => 0,
                ]);
            }

            // Deduct balance
            $user->decrement('balance', $betAmount);

            // Add player
            $match->players()->create([
                'user_id' => $user->id,
                'bet_amount' => $betAmount,
                'status' => 'active',
            ]);

            // Update total pot
            $match->increment('total_pot', $betAmount);

            // Get updated player count
            $currentPlayerCount = $match->players()->count();

            if ($gameType === 'pool_snooker') {
                if ($currentPlayerCount >= 5) {
                    $match->update(['status' => 'playing']);
                    $this->initializePoolSnookerState($match);
                } else {
                    $state = $match->game_state ?? [];
                    if ($currentPlayerCount >= 3) {
                        if (!isset($state['countdown_expires_at'])) {
                            $state['countdown_expires_at'] = now()->timestamp + 15;
                        }
                    }
                    $state['status'] = 'waiting';
                    $state['players_count'] = $currentPlayerCount;
                    $match->update(['game_state' => $state]);
                    broadcast(new \App\Events\PoolMatchUpdated($match->id, $state));
                }
            } else {
                if ($currentPlayerCount >= $maxPlayers) {
                    $match->update(['status' => 'playing']);
                    
                    if ($gameType === 'cricket') {
                        $this->initializeCricketState($match);
                    } elseif ($gameType === 'cricket3d') {
                        $this->initializeCricket3DState($match);
                    } elseif ($gameType === 'carrom') {
                        $this->initializeCarromState($match);
                    } elseif ($gameType === 'ludo') {
                        $this->initializeLudoState($match);
                    } elseif ($gameType === 'pool_1v1') {
                        $this->initializePool1v1State($match);
                    } elseif ($gameType === 'chicken_jump') {
                        $this->initializeChickenJumpState($match);
                    }
                }
            }

            return $match;
        });
    }

    private function initializeCricketState(GameMatch $match)
    {
        $players = $match->players()->orderBy('id')->pluck('user_id')->toArray();
        $gameState = [
            'innings' => [
                ['user_id' => $players[0], 'runs' => [], 'total' => 0, 'balls_faced' => 0, 'is_done' => false],
                ['user_id' => $players[1], 'runs' => [], 'total' => 0, 'balls_faced' => 0, 'is_done' => false],
            ],
            'current_innings_index' => 0,
            'current_ball' => 1,
            'max_balls' => 6,
            'status' => 'playing',
            'last_action_at' => now()->timestamp
        ];
        $match->update(['game_state' => $gameState]);
        broadcast(new \App\Events\CricketMatchUpdated($match->id, $gameState));
    }

    private function initializeCricket3DState(GameMatch $match)
    {
        $players = $match->players()->orderBy('id')->pluck('user_id')->toArray();
        $gameState = [
            'innings' => [
                ['user_id' => $players[0], 'runs' => [], 'total' => 0, 'balls_faced' => 0, 'is_done' => false],
                ['user_id' => $players[1], 'runs' => [], 'total' => 0, 'balls_faced' => 0, 'is_done' => false],
            ],
            'current_innings_index' => 0,
            'current_ball' => 1,
            'max_balls' => 6,
            'status' => 'playing',
            'last_action_at' => now()->timestamp
        ];
        $match->update(['game_state' => $gameState]);
        broadcast(new \App\Events\Cricket3DMatchUpdated($match->id, $gameState));
    }

    private function initializeCarromState(GameMatch $match)
    {
        $players = $match->players()->orderBy('id')->pluck('user_id')->toArray();
        
        $pucks = [
            ['id' => 0, 'type' => 'queen', 'x' => 250, 'y' => 250, 'isPocketed' => false]
        ];

        // Inner circle: 6 pucks alternating White/Black, radius = 32px (3 white, 3 black)
        $r1 = 32;
        for ($i = 0; $i < 6; $i++) {
            $angle = ($i * 60) * pi() / 180;
            $type = ($i % 2 === 0) ? 'white' : 'black';
            $pucks[] = [
                'id' => count($pucks),
                'type' => $type,
                'x' => 250 + $r1 * cos($angle),
                'y' => 250 + $r1 * sin($angle),
                'isPocketed' => false
            ];
        }

        $gameState = [
            'players' => [
                ['user_id' => $players[0], 'color' => 'white', 'score' => 0],
                ['user_id' => $players[1], 'color' => 'black', 'score' => 0],
            ],
            'turn_index' => 0,
            'pucks' => $pucks,
            'status' => 'playing',
            'last_action_at' => now()->timestamp,
            'last_shot' => null,
            'extra_turn' => false
        ];

        $match->update(['game_state' => $gameState]);
        broadcast(new \App\Events\CarromMatchUpdated($match->id, $gameState));
    }

    private function initializeLudoState(GameMatch $match)
    {
        $colors = ['red', 'green', 'yellow', 'blue'];
        $players = $match->players()->orderBy('id')->get();
        
        $state = [
            'player_colors' => [],
            'players_map' => [],
            'pieces' => [],
            'turn_index' => 0,
            'turn_color' => '',
            'dice_value' => null,
            'last_roll_time' => now()->timestamp,
            'status' => 'playing'
        ];

        foreach ($players as $index => $p) {
            $assignedColor = $colors[$index];
            $p->update(['color' => $assignedColor]);
            
            $state['player_colors'][] = $assignedColor;
            $state['players_map'][$assignedColor] = $p->user_id;
            $state['pieces'][$assignedColor] = [0, 0, 0, 0];
            
            if ($index === 0) {
                $state['turn_color'] = $assignedColor;
            }
        }

        $match->update(['game_state' => $state]);
        // We'll use a unified event name or specific ones
        broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
    }

    private function initializeChickenJumpState(GameMatch $match)
    {
        $players = $match->players()->orderBy('id')->pluck('user_id')->toArray();
        $gameState = [
            'players' => [
                ['user_id' => $players[0], 'score' => 0, 'alive' => true],
                ['user_id' => $players[1], 'score' => 0, 'alive' => true],
            ],
            'status'         => 'playing',
            'last_action_at' => now()->timestamp,
            'winner_id'      => null,
        ];
        $match->update(['game_state' => $gameState]);
        broadcast(new \App\Events\ChickenJumpMatchUpdated($match->id, $gameState));
    }

    public function initializePool1v1State(GameMatch $match)
    {
        $players = $match->players()->orderBy('id')->pluck('user_id')->toArray();
        
        $balls = [];
        
        // Cue ball
        $balls[] = [
            'id' => 0,
            'type' => 'cue',
            'x' => 200,
            'y' => 200,
            'color' => '#ffffff',
            'isPocketed' => false
        ];
        
        $rackPatterns = [
            ['val' => 1, 'type' => 'solid', 'color' => '#ffcc00'], // Yellow
            ['val' => 9, 'type' => 'stripe', 'color' => '#ffcc00'],
            ['val' => 2, 'type' => 'solid', 'color' => '#0033cc'], // Blue
            ['val' => 10, 'type' => 'stripe', 'color' => '#0033cc'],
            ['val' => 8, 'type' => 'black', 'color' => '#000000'], // Black
            ['val' => 3, 'type' => 'solid', 'color' => '#cc0000'], // Red
            ['val' => 11, 'type' => 'stripe', 'color' => '#cc0000'],
            ['val' => 4, 'type' => 'solid', 'color' => '#6600cc'], // Purple
            ['val' => 12, 'type' => 'stripe', 'color' => '#6600cc'],
            ['val' => 5, 'type' => 'solid', 'color' => '#ff6600'], // Orange
            ['val' => 13, 'type' => 'stripe', 'color' => '#ff6600'],
            ['val' => 6, 'type' => 'solid', 'color' => '#006600'], // Green
            ['val' => 14, 'type' => 'stripe', 'color' => '#006600'],
            ['val' => 7, 'type' => 'solid', 'color' => '#663300'], // Maroon/Brown
            ['val' => 15, 'type' => 'stripe', 'color' => '#663300']
        ];
        
        $ballRadius = 10;
        $spacing = 0.5;
        $rowSpacing = $ballRadius * sqrt(3) + $spacing;
        $colSpacing = $ballRadius * 2 + $spacing;
        
        $ballIndex = 0;
        $startX = 550;
        $startY = 200;
        
        for ($row = 0; $row < 5; $row++) {
            $x = $startX + $row * $rowSpacing;
            for ($col = 0; $col <= $row; $col++) {
                $y = $startY + ($col - $row / 2) * $colSpacing;
                $pattern = $rackPatterns[$ballIndex];
                $balls[] = [
                    'id' => $ballIndex + 1,
                    'val' => $pattern['val'],
                    'type' => $pattern['type'],
                    'color' => $pattern['color'],
                    'x' => $x,
                    'y' => $y,
                    'isPocketed' => false
                ];
                $ballIndex++;
            }
        }
        
        $gameState = [
            'players' => [
                ['user_id' => $players[0], 'group' => null, 'score' => 0],
                ['user_id' => $players[1], 'group' => null, 'score' => 0],
            ],
            'turn_index' => 0,
            'balls' => $balls,
            'status' => 'playing',
            'last_action_at' => now()->timestamp,
            'last_shot' => null
        ];
        
        $match->update(['game_state' => $gameState]);
        broadcast(new \App\Events\PoolMatchUpdated($match->id, $gameState));
    }

    public function initializePoolSnookerState(GameMatch $match)
    {
        $players = $match->players()->get();
        $playerData = [];
        foreach ($players as $index => $p) {
            $playerData[] = [
                'user_id' => $p->user_id,
                'username' => $p->user->username,
                'score' => 0,
            ];
        }
        
        $balls = [];
        $ballId = 0;
        
        // 1 Cue Ball
        $balls[] = [
            'id' => $ballId++,
            'type' => 'cue',
            'color' => '#ffffff',
            'points' => 0,
            'x' => 150,
            'y' => 200,
            'isPocketed' => false
        ];
        
        // 6 Colored Balls:
        // Yellow (2pts)
        $balls[] = [
            'id' => $ballId++,
            'type' => 'color',
            'color' => '#ffcc00',
            'points' => 2,
            'x' => 200,
            'y' => 150,
            'isPocketed' => false
        ];
        // Green (3pts)
        $balls[] = [
            'id' => $ballId++,
            'type' => 'color',
            'color' => '#006600',
            'points' => 3,
            'x' => 200,
            'y' => 250,
            'isPocketed' => false
        ];
        // Brown (4pts)
        $balls[] = [
            'id' => $ballId++,
            'type' => 'color',
            'color' => '#663300',
            'points' => 4,
            'x' => 200,
            'y' => 200,
            'isPocketed' => false
        ];
        // Blue (5pts)
        $balls[] = [
            'id' => $ballId++,
            'type' => 'color',
            'color' => '#0033cc',
            'points' => 5,
            'x' => 400,
            'y' => 200,
            'isPocketed' => false
        ];
        // Pink (6pts)
        $balls[] = [
            'id' => $ballId++,
            'type' => 'color',
            'color' => '#ff66cc',
            'points' => 6,
            'x' => 580,
            'y' => 200,
            'isPocketed' => false
        ];
        // Black (7pts)
        $balls[] = [
            'id' => $ballId++,
            'type' => 'color',
            'color' => '#000000',
            'points' => 7,
            'x' => 700,
            'y' => 200,
            'isPocketed' => false
        ];
        
        // 15 Red Balls (1pt each)
        $ballRadius = 10;
        $spacing = 0.5;
        $rowSpacing = $ballRadius * sqrt(3) + $spacing;
        $colSpacing = $ballRadius * 2 + $spacing;
        
        $startX = 595;
        $startY = 200;
        
        for ($row = 0; $row < 5; $row++) {
            $x = $startX + $row * $rowSpacing;
            for ($col = 0; $col <= $row; $col++) {
                $y = $startY + ($col - $row / 2) * $colSpacing;
                $balls[] = [
                    'id' => $ballId++,
                    'type' => 'red',
                    'color' => '#cc0000',
                    'points' => 1,
                    'x' => $x,
                    'y' => $y,
                    'isPocketed' => false
                ];
            }
        }
        
        $gameState = [
            'players' => $playerData,
            'turn_index' => 0,
            'balls' => $balls,
            'status' => 'playing',
            'last_action_at' => now()->timestamp,
            'last_shot' => null
        ];
        
        $match->update(['game_state' => $gameState]);
        broadcast(new \App\Events\PoolMatchUpdated($match->id, $gameState));
    }

    public function finalizeMatch(GameMatch $match, User $winner)
    {
        return DB::transaction(function () use ($match, $winner) {
            $totalPot = $match->total_pot;
            $winnerPlayer = $match->players()->where('user_id', $winner->id)->first();
            
            if (!$winnerPlayer) {
                throw new \Exception("Winner player not found in match.");
            }

            $winnerBet = $winnerPlayer->bet_amount;

            // Get platform fee percentage from settings (default to 10% if not found)
            $feePercent = SystemSetting::where('key', 'platform_fee')->value('value') ?? 10;
            $totalPlatformFee = ($totalPot * $feePercent) / 100;

            // Apply the user's formula
            // First, find the max bet in the match to determine if winner is "Higher Better" or "Lower Better"
            $maxBet = $match->players()->max('bet_amount');

            if ($winnerBet >= $maxBet) {
                // Higher or equal better wins
                $payout = $totalPot - $totalPlatformFee;
            } else {
                // Lower better wins
                $payout = (($totalPot) / (($totalPot / $winnerBet) / 2)) - $totalPlatformFee;
            }

            // Ensure payout is at least the original bet (optional, but good practice)
            $payout = max($payout, 0);

            // Deposit to winner
            $winner->increment('balance', $payout);

            // Mark match as completed
            $match->update([
                'status' => 'completed',
                'winner_id' => $winner->id,
            ]);

            // Update player statuses and record logs
            $game = \App\Models\Game::where('slug', $match->game_type)->first();
            $players = $match->players()->get();

            foreach ($players as $player) {
                $player->update(['status' => 'finished']);
                
                $isWinner = $player->user_id == $winner->id;
                $payoutAmount = $isWinner ? $payout : 0;
                $commission = $isWinner ? $totalPlatformFee : 0;
                
                \App\Models\GameLog::create([
                    'user_id' => $player->user_id,
                    'game_id' => $game->id ?? null,
                    'bet_amount' => $player->bet_amount,
                    'payout_amount' => $payoutAmount,
                    'commission_amount' => $commission,
                    'result' => $isWinner ? 'win' : 'lose',
                    'game_data' => [
                        'match_id' => $match->id,
                        'room_code' => $match->room_code,
                        'total_pot' => $totalPot
                    ]
                ]);
            }

            return [
                'match' => $match,
                'payout' => $payout
            ];
        });
    }

    public function handleDraw(GameMatch $match)
    {
        return DB::transaction(function () use ($match) {
            $feePercent = SystemSetting::where('key', 'platform_fee')->value('value') ?? 10;
            $players = $match->players()->with('user')->get();
            $game = \App\Models\Game::where('slug', $match->game_type)->first();

            foreach ($players as $player) {
                // Refund bet - fee
                $refund = $player->bet_amount * (1 - ($feePercent / 100));
                $player->user->increment('balance', $refund);
                $player->update(['status' => 'finished']);

                \App\Models\GameLog::create([
                    'user_id' => $player->user_id,
                    'game_id' => $game->id ?? null,
                    'bet_amount' => $player->bet_amount,
                    'payout_amount' => $refund,
                    'commission_amount' => $player->bet_amount * ($feePercent / 100),
                    'result' => 'draw',
                    'game_data' => [
                        'match_id' => $match->id,
                        'room_code' => $match->room_code,
                        'is_draw' => true
                    ]
                ]);
            }

            $match->update([
                'status' => 'completed',
                'winner_id' => null,
            ]);

            return ['payout' => null, 'is_draw' => true];
        });
    }
    /**
     * Handle match timeout (AFK).
     */
    public function handleTimeout(GameMatch $match)
    {
        $state = $match->game_state;
        if (!$state || !isset($state['status']) || $state['status'] !== 'playing') return;

        // Current player who should have acted but didn't
        if ($match->game_type === 'cricket') {
            $currentInnings = $state['innings'][$state['current_innings_index']];
            $timeoutUserId = $currentInnings['user_id'];
        } elseif ($match->game_type === 'carrom') {
            $timeoutUserId = $state['players'][$state['turn_index']]['user_id'];
        } elseif ($match->game_type === 'pool_1v1' || $match->game_type === 'pool_snooker') {
            $timeoutUserId = $state['players'][$state['turn_index']]['user_id'];
        } elseif ($match->game_type === 'ludo') {
            $timeoutUserId = $state['players_map'][$state['turn_color']];
        } else {
            return;
        }

        // Find the other player (winner)
        if ($match->game_type === 'pool_snooker') {
            // Award win to the player with the highest score among the non-timeout players
            $otherPlayers = $match->players()->where('user_id', '!=', $timeoutUserId)->pluck('user_id')->toArray();
            $highestScore = -1;
            $winnerUserId = reset($otherPlayers);
            foreach ($state['players'] as $p) {
                if ($p['user_id'] != $timeoutUserId && $p['score'] > $highestScore) {
                    $highestScore = $p['score'];
                    $winnerUserId = $p['user_id'];
                }
            }
            $winner = User::find($winnerUserId);
        } else {
            $winnerPlayer = $match->players()->where('user_id', '!=', $timeoutUserId)->first();
            if (!$winnerPlayer) return;
            $winner = User::find($winnerPlayer->user_id);
        }
        
        // Finalize match with the winner
        $result = $this->finalizeMatch($match, $winner);

        // Update state to finished
        $state['status'] = 'finished';
        $state['winner_id'] = $winner->id;
        $state['timeout_loser_id'] = $timeoutUserId;
        $state['payout'] = $result['payout'];
        $match->update(['game_state' => $state]);

        // Broadcast update
        if ($match->game_type === 'cricket') {
            broadcast(new \App\Events\CricketMatchUpdated($match->id, $state));
        } elseif ($match->game_type === 'cricket3d') {
            broadcast(new \App\Events\Cricket3DMatchUpdated($match->id, $state));
        } elseif ($match->game_type === 'carrom') {
            broadcast(new \App\Events\CarromMatchUpdated($match->id, $state));
        } elseif ($match->game_type === 'pool_1v1' || $match->game_type === 'pool_snooker') {
            broadcast(new \App\Events\PoolMatchUpdated($match->id, $state));
        } elseif ($match->game_type === 'ludo') {
            broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
        } elseif ($match->game_type === 'chicken_jump') {
            broadcast(new \App\Events\ChickenJumpMatchUpdated($match->id, $state));
        }
    }
    /**
     * Handle match forfeit (leaving the game).
     */
    public function handleForfeit(GameMatch $match, User $loser)
    {
        $state = $match->game_state;
        if (!$state || !isset($state['status']) || $state['status'] !== 'playing') return;

        // Find the other player (winner)
        if ($match->game_type === 'pool_snooker') {
            // Award win to the player with the highest score among the non-forfeiting players
            $otherPlayers = $match->players()->where('user_id', '!=', $loser->id)->pluck('user_id')->toArray();
            $highestScore = -1;
            $winnerUserId = reset($otherPlayers);
            foreach ($state['players'] as $p) {
                if ($p['user_id'] != $loser->id && $p['score'] > $highestScore) {
                    $highestScore = $p['score'];
                    $winnerUserId = $p['user_id'];
                }
            }
            $winner = User::find($winnerUserId);
        } else {
            $winnerPlayer = $match->players()->where('user_id', '!=', $loser->id)->first();
            if (!$winnerPlayer) return;
            $winner = User::find($winnerPlayer->user_id);
        }
        
        // Finalize match with the winner
        $result = $this->finalizeMatch($match, $winner);

        // Update state to finished
        $state['status'] = 'finished';
        $state['winner_id'] = $winner->id;
        $state['forfeit_loser_id'] = $loser->id;
        $state['payout'] = $result['payout'];
        $match->update(['game_state' => $state]);

        // Broadcast update
        if ($match->game_type === 'cricket') {
            broadcast(new \App\Events\CricketMatchUpdated($match->id, $state));
        } elseif ($match->game_type === 'cricket3d') {
            broadcast(new \App\Events\Cricket3DMatchUpdated($match->id, $state));
        } elseif ($match->game_type === 'carrom') {
            broadcast(new \App\Events\CarromMatchUpdated($match->id, $state));
        } elseif ($match->game_type === 'pool_1v1' || $match->game_type === 'pool_snooker') {
            broadcast(new \App\Events\PoolMatchUpdated($match->id, $state));
        } elseif ($match->game_type === 'ludo') {
            broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
        } elseif ($match->game_type === 'chicken_jump') {
            broadcast(new \App\Events\ChickenJumpMatchUpdated($match->id, $state));
        }
    }
}
