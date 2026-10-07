<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\SuperAgentDashboardController;
use App\Http\Controllers\Api\ContentBonusController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\StatementController;
use App\Http\Controllers\Api\SuperAgentApplicationController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\AdminTournamentController;
use App\Http\Controllers\TournamentMatchController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Illuminate\Support\Facades\Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile Routes
    Route::post('/user/profile', [ProfileController::class, 'update']);
    Route::get('/user/logins', [ProfileController::class, 'logins']);

    // Wallet Routes
    Route::post('/deposit', [WalletController::class, 'deposit']);
    Route::post('/deposit/{id}/confirm', [WalletController::class, 'confirmDeposit']);
    Route::post('/withdraw', [WalletController::class, 'withdraw']);
    Route::get('/transactions', [WalletController::class, 'transactions']);
    Route::get('/deposit-methods', [WalletController::class, 'getMethods']);
    Route::get('/withdrawal-methods', [WalletController::class, 'getWithdrawalMethods']);
    Route::get('/user/withdrawal-accounts', [WalletController::class, 'getUserWithdrawalAccounts']);
    Route::post('/user/withdrawal-accounts', [WalletController::class, 'bindUserWithdrawalAccount']);
    Route::delete('/user/withdrawal-accounts/{id}', [WalletController::class, 'unbindUserWithdrawalAccount']);
    Route::get('/user/team', [WalletController::class, 'getTeamData']);

    // Game Routes
    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/launch/{slug}', [GameController::class, 'launch']);
    Route::post('/games/bet', [GameController::class, 'bet']);
    Route::post('/games/settle/{log_id}', [GameController::class, 'settle']);
    
    // Chicken Game Routes
    Route::get('/games/chicken/jackpot', [\App\Http\Controllers\ChickenGameController::class, 'getJackpot']);
    Route::post('/games/chicken/start', [\App\Http\Controllers\ChickenGameController::class, 'start']);
    Route::post('/games/chicken/move', [\App\Http\Controllers\ChickenGameController::class, 'move']);
    Route::post('/games/chicken/cashout', [\App\Http\Controllers\ChickenGameController::class, 'cashout']);
    
    // Dino Cross Game Routes
    Route::get('/games/dino/jackpot', [\App\Http\Controllers\DinoCrossController::class, 'getJackpot']);
    Route::post('/games/dino/start', [\App\Http\Controllers\DinoCrossController::class, 'start']);
    Route::post('/games/dino/move', [\App\Http\Controllers\DinoCrossController::class, 'move']);
    Route::post('/games/dino/cashout', [\App\Http\Controllers\DinoCrossController::class, 'cashout']);
    
    // Chicken Fight (Sabong) Routes
    Route::get('/games/chicken-fight/state', [\App\Http\Controllers\ChickenFightController::class, 'getState']);
    Route::post('/games/chicken-fight/bet', [\App\Http\Controllers\ChickenFightController::class, 'placeBet']);
    
    // Aviator Routes
    Route::get('/games/aviator/state', [\App\Http\Controllers\AviatorController::class, 'getState']);
    Route::post('/games/aviator/bet', [\App\Http\Controllers\AviatorController::class, 'placeBet']);
    Route::post('/games/aviator/cashout', [\App\Http\Controllers\AviatorController::class, 'cashout']);

    // Tom & Jerry Routes
    Route::get('/games/tom-jerry/state', [\App\Http\Controllers\TomJerryController::class, 'getState']);
    Route::post('/games/tom-jerry/bet', [\App\Http\Controllers\TomJerryController::class, 'placeBet']);

    // Teen Patti (3 Patti) Routes
    Route::get('/games/three-patti/boots', [\App\Http\Controllers\ThreePattiController::class, 'getBoots']);
    Route::post('/games/three-patti/join', [\App\Http\Controllers\ThreePattiController::class, 'joinTable']);
    Route::post('/games/three-patti/leave', [\App\Http\Controllers\ThreePattiController::class, 'leaveTable']);
    Route::get('/games/three-patti/table/{code}', [\App\Http\Controllers\ThreePattiController::class, 'getTable']);
    Route::post('/games/three-patti/seen', [\App\Http\Controllers\ThreePattiController::class, 'lookAtCards']);
    Route::post('/games/three-patti/chaal', [\App\Http\Controllers\ThreePattiController::class, 'placeChaal']);
    Route::post('/games/three-patti/raise', [\App\Http\Controllers\ThreePattiController::class, 'placeRaise']);
    Route::post('/games/three-patti/fold', [\App\Http\Controllers\ThreePattiController::class, 'packHand']);
    Route::post('/games/three-patti/sideshow/request', [\App\Http\Controllers\ThreePattiController::class, 'requestSideshow']);
    Route::post('/games/three-patti/sideshow/respond', [\App\Http\Controllers\ThreePattiController::class, 'respondSideshow']);
    Route::post('/games/three-patti/show', [\App\Http\Controllers\ThreePattiController::class, 'triggerShow']);

    Route::get('/activity', [GameController::class, 'activity']);
    Route::get('/leaderboard', [GameController::class, 'leaderboard']);
    Route::get('/settings', [GameController::class, 'settings']);
    Route::get('/user/stats', [\App\Http\Controllers\StatsController::class, 'getStats']);

    // Ludo Routes
    Route::middleware('auth:sanctum')->prefix('ludo')->group(function () {
        Route::get('/current', [\App\Http\Controllers\LudoController::class, 'currentMatch']);
        Route::post('/join', [\App\Http\Controllers\LudoController::class, 'join']);
        Route::post('/cancel', [\App\Http\Controllers\LudoController::class, 'cancelMatch']);
        Route::get('/room/{code}', [\App\Http\Controllers\LudoController::class, 'getRoom']);
        Route::post('/roll', [\App\Http\Controllers\LudoController::class, 'rollDice']);
        Route::post('/move', [\App\Http\Controllers\LudoController::class, 'movePiece']);
        Route::post('/timeout', [\App\Http\Controllers\LudoController::class, 'timeout']);
        Route::post('/kick-disconnected', [\App\Http\Controllers\LudoController::class, 'kickDisconnected']);
        Route::post('/leave', [\App\Http\Controllers\LudoController::class, 'leaveMatch']);
    });
    
    // Cricket Routes
    Route::middleware('auth:sanctum')->prefix('cricket')->group(function () {
        Route::post('/join', [\App\Http\Controllers\CricketController::class, 'join']);
        Route::post('/cancel-match', [\App\Http\Controllers\CricketController::class, 'cancelMatch']);
        Route::get('/active-match', [\App\Http\Controllers\CricketController::class, 'activeMatch']);
        Route::get('/match/{code}', [\App\Http\Controllers\CricketController::class, 'getMatch']);
        Route::post('/swing/{code}', [\App\Http\Controllers\CricketController::class, 'submitSwing']);
        Route::post('/forfeit/{code}', [\App\Http\Controllers\CricketController::class, 'forfeit']);
    });

    // Cricket 3D Routes
    Route::middleware('auth:sanctum')->prefix('cricket3d')->group(function () {
        Route::post('/join', [\App\Http\Controllers\Cricket3DController::class, 'join']);
        Route::post('/cancel-match', [\App\Http\Controllers\Cricket3DController::class, 'cancelMatch']);
        Route::get('/active-match', [\App\Http\Controllers\Cricket3DController::class, 'activeMatch']);
        Route::get('/match/{code}', [\App\Http\Controllers\Cricket3DController::class, 'getMatch']);
        Route::post('/swing/{code}', [\App\Http\Controllers\Cricket3DController::class, 'submitSwing']);
        Route::post('/forfeit/{code}', [\App\Http\Controllers\Cricket3DController::class, 'forfeit']);
    });

    // Carrom Routes
    Route::middleware('auth:sanctum')->prefix('carrom')->group(function () {
        Route::post('/join', [\App\Http\Controllers\CarromController::class, 'join']);
        Route::post('/cancel-match', [\App\Http\Controllers\CarromController::class, 'cancelMatch']);
        Route::get('/active-match', [\App\Http\Controllers\CarromController::class, 'activeMatch']);
        Route::get('/match/{code}', [\App\Http\Controllers\CarromController::class, 'getMatch']);
        Route::post('/strike/{code}', [\App\Http\Controllers\CarromController::class, 'submitStrike']);
        Route::post('/update-state/{code}', [\App\Http\Controllers\CarromController::class, 'updateState']);
        Route::post('/forfeit/{code}', [\App\Http\Controllers\CarromController::class, 'forfeit']);
    });

    // Pool Routes
    Route::middleware('auth:sanctum')->prefix('pool')->group(function () {
        Route::post('/join', [\App\Http\Controllers\PoolController::class, 'join']);
        Route::post('/cancel-match', [\App\Http\Controllers\PoolController::class, 'cancelMatch']);
        Route::get('/active-match', [\App\Http\Controllers\PoolController::class, 'activeMatch']);
        Route::get('/match/{code}', [\App\Http\Controllers\PoolController::class, 'getMatch']);
        Route::post('/strike/{code}', [\App\Http\Controllers\PoolController::class, 'submitStrike']);
        Route::post('/update-state/{code}', [\App\Http\Controllers\PoolController::class, 'updateState']);
        Route::post('/forfeit/{code}', [\App\Http\Controllers\PoolController::class, 'forfeit']);
    });

    // Chicken Jump Routes
    Route::middleware('auth:sanctum')->prefix('chicken-jump')->group(function () {
        Route::post('/join', [\App\Http\Controllers\ChickenJumpController::class, 'join']);
        Route::post('/cancel-match', [\App\Http\Controllers\ChickenJumpController::class, 'cancelMatch']);
        Route::get('/active-match', [\App\Http\Controllers\ChickenJumpController::class, 'activeMatch']);
        Route::get('/match/{code}', [\App\Http\Controllers\ChickenJumpController::class, 'getMatch']);
        Route::post('/update/{code}', [\App\Http\Controllers\ChickenJumpController::class, 'updateState']);
        Route::post('/forfeit/{code}', [\App\Http\Controllers\ChickenJumpController::class, 'forfeit']);
    });

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/transactions/pending', [AdminController::class, 'pendingTransactions']);
        Route::post('/transactions/{id}/update', [AdminController::class, 'updateTransaction']);
    });

    // Super Agent Routes (Protected by super_agent middleware)
    Route::middleware('super_agent')->prefix('super-agent')->group(function () {
        Route::get('/dashboard', [SuperAgentDashboardController::class, 'dashboard']);
        Route::get('/earnings', [SuperAgentDashboardController::class, 'earnings']);
        Route::get('/team', [SuperAgentDashboardController::class, 'team']);
        Route::get('/leaderboard', [SuperAgentDashboardController::class, 'leaderboard']);
        Route::post('/update-team-name', [SuperAgentDashboardController::class, 'updateTeamName']);
        Route::post('/regenerate-link', [SuperAgentDashboardController::class, 'regenerateLink']);

        Route::post('/content-bonus/request', [ContentBonusController::class, 'store']);
        Route::get('/content-bonus/requests', [ContentBonusController::class, 'index']);

        Route::get('/challenges', [ChallengeController::class, 'index']);

        Route::get('/statement/{year}/{month}', [StatementController::class, 'show']);
        Route::get('/statement/{year}/{month}/html', [StatementController::class, 'showHtml']);
    });

    // Super Agent Application Routes (public, but auth required)
    Route::post('/super-agent/apply', [SuperAgentApplicationController::class, 'apply']);
    Route::get('/super-agent/application-status', [SuperAgentApplicationController::class, 'status']);

    // WebPush Subscription Routes
    Route::get('/push/vapid-key', [\App\Http\Controllers\PushSubscriptionController::class, 'vapidKey']);
    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store']);
    Route::post('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy']);

    // Tournament Routes
    Route::get('/tournaments', [TournamentController::class, 'index']);
    Route::get('/tournaments/active', [TournamentController::class, 'activeTournaments']);
    Route::get('/tournaments/leaderboard', [\App\Http\Controllers\TournamentLeaderboardController::class, 'index']);
    Route::get('/tournaments/user/registrations', [TournamentController::class, 'userRegistrations']);
    Route::get('/tournaments/user/bets', [\App\Http\Controllers\TournamentBetController::class, 'userBets']);
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
    Route::get('/tournaments/{tournament}/bracket', [TournamentController::class, 'bracket']);
    Route::get('/tournaments/{tournament}/leaderboard', [TournamentController::class, 'leaderboard']);
    Route::post('/tournaments/{tournament}/register', [TournamentController::class, 'register']);

    // Tournament Betting Routes
    Route::post('/tournament-matches/{match}/bet', [\App\Http\Controllers\TournamentBetController::class, 'placeBet']);

    // Tournament Match Routes
    Route::post('/tournament-matches/{match}/ready', [TournamentMatchController::class, 'setReady']);
    Route::post('/tournament-matches/{match}/result', [TournamentMatchController::class, 'submitResult']);
    Route::post('/tournament-matches/{match}/forfeit', [TournamentMatchController::class, 'forfeit']);
    Route::post('/tournament-matches/{match}/disconnect', [TournamentMatchController::class, 'reportDisconnect']);
    Route::post('/tournament-matches/{match}/check-timeout', [TournamentMatchController::class, 'checkReadyTimeout']);

    // Admin Tournament Routes
    Route::middleware('admin')->prefix('admin/tournaments')->group(function () {
        Route::post('/', [TournamentController::class, 'store']);
        Route::put('/{tournament}', [TournamentController::class, 'update']);
        Route::delete('/{tournament}', [TournamentController::class, 'destroy']);
        Route::post('/{tournament}/generate-bracket', [TournamentController::class, 'generateBracket']);
        Route::post('/{tournament}/cancel', [TournamentController::class, 'cancel']);
        
        // Admin Tournament Management
        Route::get('/list', [AdminTournamentController::class, 'tournamentsList']);
        Route::get('/dashboard', [AdminTournamentController::class, 'dashboard']);
        Route::get('/{tournament}/audit', [AdminTournamentController::class, 'audit']);
        Route::get('/matches/{match}', [AdminTournamentController::class, 'matchDetails']);
        Route::put('/matches/{match}/result', [AdminTournamentController::class, 'updateMatchResult']);
        Route::post('/matches/{match}/advance', [AdminTournamentController::class, 'forceAdvanceMatch']);
    });
});
