<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GameMatch;
use App\Services\GameService;
use Carbon\Carbon;

class CheckGameTimeouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:check-timeouts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for games where a player has been inactive for more than 60 seconds.';

    /**
     * Execute the console command.
     */
    public function handle(GameService $gameService)
    {
        $matches = GameMatch::where('status', 'playing')->get();

        foreach ($matches as $match) {
            $state = $match->game_state;
            
            if (in_array($match->game_type, ['cricket', 'carrom'])) {
                if (isset($state['last_action_at']) && isset($state['innings']) && isset($state['current_innings_index'])) {
                    $currentInnings = $state['innings'][$state['current_innings_index']];
                    if (isset($currentInnings['balls_faced']) && $currentInnings['balls_faced'] == 0) {
                        $diff = now()->timestamp - $state['last_action_at'];
                        if ($diff > 10) {
                            $this->info("Match {$match->room_code} timed out ({$diff}s). Handling...");
                            $gameService->handleTimeout($match);
                        }
                    }
                }
            } else {
                if (isset($state['last_action_at'])) {
                    $lastAction = $state['last_action_at'];
                    $diff = now()->timestamp - $lastAction;

                    if ($diff > 60) {
                        $this->info("Match {$match->room_code} timed out ({$diff}s). Handling...");
                        $gameService->handleTimeout($match);
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}
