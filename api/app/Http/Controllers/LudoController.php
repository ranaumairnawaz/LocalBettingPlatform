<?php

namespace App\Http\Controllers;

use App\Models\LudoRoom;
use App\Models\LudoRoomPlayer;
use App\Models\LudoGameLog;
use App\Models\SystemSetting;
use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LudoController extends Controller
{
    protected $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }
    public function join(Request $request)
    {
        $request->validate([
            'entry_fee' => 'required|numeric|min:10',
            'max_players' => 'required|in:2,4',
        ]);

        try {
            $match = $this->gameService->joinMatch(
                $request->user(), 
                'ludo', 
                $request->entry_fee, 
                $request->max_players
            );

            return response()->json([
                'room' => $match->load('players.user'),
                'room_code' => $match->room_code
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function getRoom($code)
    {
        $match = \App\Models\GameMatch::where('room_code', $code)->with('players.user')->firstOrFail();
        return response()->json(['room' => $match]);
    }

    public function currentMatch(Request $request)
    {
        $existing = \App\Models\GameMatchPlayer::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->whereHas('match', function ($q) {
                $q->where('game_type', 'ludo')
                  ->whereIn('status', ['waiting', 'playing']);
            })->first();

        if ($existing) {
            return response()->json([
                'room' => $existing->match->load('players.user'),
                'room_code' => $existing->match->room_code
            ]);
        }

        return response()->json(['room' => null]);
    }

    public function cancelMatch(Request $request)
    {
        $request->validate(['room_code' => 'required']);
        $user = $request->user();

        $match = \App\Models\GameMatch::where('room_code', $request->room_code)->firstOrFail();
        
        if ($match->status !== 'waiting') {
            return response()->json(['message' => 'Game has already started!'], 400);
        }
        
        $player = $match->players()->where('user_id', $user->id)->firstOrFail();

        // Refund balance
        $user->increment('balance', $player->bet_amount);
        $player->delete();

        if ($match->players()->count() === 0) {
            $match->update(['status' => 'cancelled']);
        } else {
            // Re-broadcast join list
            broadcast(new \App\Events\LudoBoardUpdated($match->id, ['player_left' => $user->id]));
        }

        return response()->json(['message' => 'Match cancelled and refunded.']);
    }

    public function leaveMatch(Request $request)
    {
        $user = $request->user();
        $existing = \App\Models\GameMatchPlayer::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('match', function ($q) {
                $q->where('game_type', 'ludo')
                  ->whereIn('status', ['waiting', 'playing']);
            })->first();

        if (!$existing) return response()->json(['message' => 'Not in a game.']);

        $match = $existing->match;

        if ($match->status === 'waiting') {
            $request->merge(['room_code' => $match->room_code]);
            return $this->cancelMatch($request);
        } else if ($match->status === 'playing') {
            $existing->update(['status' => 'disconnected']);
            
            $state = $match->game_state;
            $colors = $state['player_colors'];
            if (($key = array_search($existing->color, $colors)) !== false) {
                unset($colors[$key]);
            }
            $state['player_colors'] = array_values($colors);

            if (count($state['player_colors']) === 1) {
                $lastPlayer = $match->players()->where('color', $state['player_colors'][0])->first()->user;
                return $this->endGame($match, $lastPlayer);
            } else {
                if ($state['turn_color'] === $existing->color) {
                    $this->nextTurn($match, $state);
                } else {
                    $match->update(['game_state' => $state]);
                    broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function rollDice(Request $request)
    {
        $request->validate(['room_code' => 'required']);
        $user = $request->user();

        $match = \App\Models\GameMatch::where('room_code', $request->room_code)
            ->where('status', 'playing')
            ->firstOrFail();

        $state = $match->game_state;
        $currentPlayer = $match->players()->where('color', $state['turn_color'])->first();

        if (!$currentPlayer || $currentPlayer->user_id !== $user->id) {
            return response()->json(['message' => 'Not your turn.'], 403);
        }

        if ($state['dice_value'] !== null) {
            return response()->json(['message' => 'Already rolled.'], 422);
        }

        $dice = rand(1, 6);
        $state['dice_value'] = $dice;
        $state['last_roll_time'] = now()->timestamp;

        broadcast(new \App\Events\LudoDiceRolled($match->id, $dice, $state['turn_color']));

        if (!$this->hasValidMoves($state['turn_color'], $dice, $state)) {
            $state['turn_index'] = ($state['turn_index'] + 1) % count($state['player_colors']);
            $state['turn_color'] = $state['player_colors'][$state['turn_index']];
            $state['dice_value'] = null;
            $state['last_roll_time'] = now()->timestamp;
            $match->update(['game_state' => $state]);
            
            broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
            
            return response()->json(['dice' => $dice, 'passed' => true, 'game_state' => $state]);
        }

        $match->update(['game_state' => $state]);
        broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
        
        return response()->json(['dice' => $dice, 'passed' => false, 'game_state' => $state]);
    }

    public function movePiece(Request $request)
    {
        $request->validate([
            'room_code' => 'required',
            'piece_index' => 'required|integer|min:0|max:3'
        ]);

        $user = $request->user();
        $match = \App\Models\GameMatch::where('room_code', $request->room_code)
            ->where('status', 'playing')
            ->firstOrFail();

        $state = $match->game_state;
        $currentPlayer = $match->players()->where('color', $state['turn_color'])->first();

        if (!$currentPlayer || $currentPlayer->user_id !== $user->id) {
            return response()->json(['message' => 'Not your turn.'], 403);
        }

        if ($state['dice_value'] === null) {
            return response()->json(['message' => 'Roll the dice first.'], 422);
        }

        $color = $state['turn_color'];
        $pIndex = $request->piece_index;
        $dice = $state['dice_value'];

        $currentPos = $state['pieces'][$color][$pIndex];
        $extraRoll = false;

        if ($currentPos === 0) {
            if ($dice === 6) {
                $state['pieces'][$color][$pIndex] = 1;
                $extraRoll = true;
            } else {
                return response()->json(['message' => 'You need a 6 to move out of base.'], 422);
            }
        } else {
            $newPos = $currentPos + $dice;
            if ($newPos > 57) return response()->json(['message' => 'Invalid move.'], 422);
            
            $state['pieces'][$color][$pIndex] = $newPos;

            if ($newPos === 57) {
                $extraRoll = true;
                $hasWon = true;
                foreach ($state['pieces'][$color] as $p) {
                    if ($p !== 57) $hasWon = false;
                }
                if ($hasWon) return $this->endGame($match, $user);
            } else if ($newPos <= 51) {
                $globalPos = $this->getGlobalPosition($color, $newPos);
                $safeSquares = [0, 8, 13, 21, 26, 34, 39, 47];
                
                if (!in_array($globalPos, $safeSquares)) {
                    foreach ($state['player_colors'] as $oppColor) {
                        if ($oppColor === $color) continue;
                        $oppPiecesOnSquare = [];
                        foreach ($state['pieces'][$oppColor] as $oppIdx => $oppPos) {
                            if ($oppPos >= 1 && $oppPos <= 51) {
                                if ($this->getGlobalPosition($oppColor, $oppPos) === $globalPos) {
                                    $oppPiecesOnSquare[] = $oppIdx;
                                }
                            }
                        }
                        if (count($oppPiecesOnSquare) === 1) {
                            $state['pieces'][$oppColor][$oppPiecesOnSquare[0]] = 0;
                            $extraRoll = true;
                        }
                    }
                }
            }
        }

        if ($dice === 6) $extraRoll = true;

        if (!$extraRoll) {
            $state['turn_index'] = ($state['turn_index'] + 1) % count($state['player_colors']);
            $state['turn_color'] = $state['player_colors'][$state['turn_index']];
        }

        $state['dice_value'] = null;
        $state['last_roll_time'] = now()->timestamp;
        
        $match->update(['game_state' => $state]);
        broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));

        return response()->json(['success' => true, 'game_state' => $state]);
    }

    public function timeout(Request $request)
    {
        $request->validate(['room_code' => 'required']);
        $match = \App\Models\GameMatch::where('room_code', $request->room_code)->where('status', 'playing')->firstOrFail();

        $state = $match->game_state;
        if (now()->timestamp - $state['last_roll_time'] > 15) {
            $this->nextTurn($match, $state);
            return response()->json(['message' => 'Turn skipped.', 'game_state' => $match->refresh()->game_state]);
        }

        return response()->json(['message' => 'Too early.'], 422);
    }

    private function nextTurn(\App\Models\GameMatch $match, array $state)
    {
        $players = $state['player_colors'];
        $state['turn_index'] = ($state['turn_index'] + 1) % count($players);
        $state['turn_color'] = $players[$state['turn_index']];
        $state['dice_value'] = null;
        $state['last_roll_time'] = now()->timestamp;

        $match->update(['game_state' => $state]);
        broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
    }

    private function endGame(\App\Models\GameMatch $match, \App\Models\User $winner)
    {
        $result = $this->gameService->finalizeMatch($match, $winner);
        $state = $match->game_state;
        $state['status'] = 'finished';
        $state['winner'] = $winner->username;
        $state['payout'] = $result['payout'];
        $match->update(['game_state' => $state]);

        broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
        return response()->json(['message' => 'Game Over', 'winner' => $winner->username]);
    }

    private function hasValidMoves($color, $dice, $state) {
        foreach ($state['pieces'][$color] as $pos) {
            if ($pos === 0 && $dice === 6) return true;
            if ($pos > 0 && $pos + $dice <= 57) return true;
        }
        return false;
    }

    private function getGlobalPosition($color, $localPos) {
        $offsets = ['red' => 0, 'green' => 13, 'yellow' => 26, 'blue' => 39];
        return ($offsets[$color] + $localPos - 1) % 52;
    }

    public function kickDisconnected(Request $request)
    {
        $request->validate([
            'room_code' => 'required',
            'player_id' => 'required|exists:game_match_players,user_id'
        ]);

        $match = \App\Models\GameMatch::where('room_code', $request->room_code)->firstOrFail();
        $targetPlayer = $match->players()->where('user_id', $request->player_id)->firstOrFail();
        $targetPlayer->update(['status' => 'disconnected']);

        if ($match->status === 'playing') {
            $state = $match->game_state;
            $colors = $state['player_colors'];
            if (($key = array_search($targetPlayer->color, $colors)) !== false) unset($colors[$key]);
            $state['player_colors'] = array_values($colors);

            if ($state['turn_color'] === $targetPlayer->color) $this->nextTurn($match, $state);
            else {
                $match->update(['game_state' => $state]);
                broadcast(new \App\Events\LudoBoardUpdated($match->id, $state));
            }

            if (count($state['player_colors']) === 1) {
                $lastPlayer = $match->players()->where('color', $state['player_colors'][0])->first()->user;
                return $this->endGame($match, $lastPlayer);
            }
        }

        return response()->json(['message' => 'Player kicked.']);
    }
}
