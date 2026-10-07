<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\SuperAgentApplicationController;
use App\Http\Controllers\Admin\SuperAgentManagementController;
use App\Http\Controllers\Admin\ContentBonusController;
use App\Http\Controllers\Admin\SuperAgentChallengeController;
use App\Http\Controllers\Admin\SuperAgentSettingsController;

Route::get('/', function () {
    if (file_exists(public_path('index.html'))) {
        return file_get_contents(public_path('index.html'));
    }
    return view('welcome');
});

Route::get('/login', [AdminController::class, 'bladeLogin'])->name('login');
Route::post('/login', [AdminController::class, 'bladeLoginSubmit'])->name('admin.login.submit');
Route::post('/logout', [AdminController::class, 'bladeLogout'])->name('logout');

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'bladeDashboard'])->name('admin.dashboard');
    Route::get('/transactions', [AdminController::class, 'bladeTransactions'])->name('admin.transactions');
    Route::get('/transactions/pending-deposits', [AdminController::class, 'bladePendingDeposits'])->name('admin.transactions.pending_deposits');
    Route::get('/transactions/pending-withdrawals', [AdminController::class, 'bladePendingWithdrawals'])->name('admin.transactions.pending_withdrawals');
    Route::post('/transactions/{id}/update', [AdminController::class, 'bladeUpdateTransaction'])->name('admin.transactions.update');

    // Security & Fraud Detection
    Route::get('/fraud-detect', [AdminController::class, 'bladeFraudDetect'])->name('admin.fraud_detect');
    Route::post('/fraud-detect/batch-ban', [AdminController::class, 'bladeIpBatchBan'])->name('admin.fraud_detect.batch_ban');

    // Deposit Methods Management
    Route::get('/deposit-methods', [AdminController::class, 'bladeDepositMethods'])->name('admin.deposit_methods');
    Route::post('/deposit-methods', [AdminController::class, 'bladeStoreDepositMethod'])->name('admin.deposit_methods.store');

    // Withdrawal Methods Management
    Route::get('/withdrawal-methods', [AdminController::class, 'bladeWithdrawalMethods'])->name('admin.withdrawal_methods');
    Route::post('/withdrawal-methods', [AdminController::class, 'bladeStoreWithdrawalMethod'])->name('admin.withdrawal_methods.store');
    Route::post('/withdrawal-methods/{id}/toggle', [AdminController::class, 'bladeToggleWithdrawalMethod'])->name('admin.withdrawal_methods.toggle');
    Route::post('/withdrawal-methods/{id}/delete', [AdminController::class, 'bladeDeleteWithdrawalMethod'])->name('admin.withdrawal_methods.delete');

    // Method Accounts Management
    Route::post('/deposit-methods/{method_id}/accounts', [AdminController::class, 'bladeStoreMethodAccount'])->name('admin.method_accounts.store');

    // Account Values Management
    Route::post('/method-accounts/{account_id}/values', [AdminController::class, 'bladeStoreAccountValue'])->name('admin.account_values.store');

    // Users Management
    Route::get('/users', [AdminController::class, 'bladeUsers'])->name('admin.users');
    Route::get('/users/{id}', [AdminController::class, 'bladeUserShow'])->name('admin.users.show');
    Route::post('/users/{id}/ban', [AdminController::class, 'bladeUserBan'])->name('admin.users.ban');
    Route::post('/users/{id}/adjust-balance', [AdminController::class, 'bladeAdjustBalance'])->name('admin.users.adjust_balance');

    // Games Management
    Route::get('/games', [AdminController::class, 'bladeGames'])->name('admin.games');
    Route::get('/games/{id}', [AdminController::class, 'bladeGameShow'])->name('admin.games.show');
    Route::post('/games/{id}/update', [AdminController::class, 'bladeUpdateGame'])->name('admin.games.update');
    Route::post('/games/{id}/toggle', [AdminController::class, 'bladeToggleGame'])->name('admin.games.toggle');
    Route::post('/games/{id}/toggle-featured', [AdminController::class, 'bladeToggleFeatured'])->name('admin.games.toggle-featured');

    // Tournament Management
    Route::get('/tournaments/scheduler', [AdminController::class, 'bladeTournamentScheduler'])->name('admin.tournaments.scheduler');
    Route::get('/tournaments/audit', [AdminController::class, 'bladeTournamentAudit'])->name('admin.tournaments.audit');

    // Tournament Admin JSON API (Session Authenticated)
    Route::prefix('api')->group(function () {
        Route::get('/games', [AdminController::class, 'bladeGetGamesJSON']);
        Route::get('/tournaments/list', [\App\Http\Controllers\AdminTournamentController::class, 'tournamentsList']);
        Route::get('/tournaments/dashboard', [\App\Http\Controllers\AdminTournamentController::class, 'dashboard']);
        Route::get('/tournaments/{tournament}/audit', [\App\Http\Controllers\AdminTournamentController::class, 'audit']);
        Route::post('/tournaments', [\App\Http\Controllers\TournamentController::class, 'store']);
        Route::post('/tournaments/{tournament}/generate-bracket', [\App\Http\Controllers\TournamentController::class, 'generateBracket']);
        Route::post('/tournaments/{tournament}/cancel', [\App\Http\Controllers\TournamentController::class, 'cancel']);
    });

    // Settings Management
    Route::get('/settings', [AdminController::class, 'bladeSettings'])->name('admin.settings');
    Route::post('/settings', [AdminController::class, 'bladeUpdateSettings'])->name('admin.settings.update');

    // Super Agent Applications Management
    Route::get('/super-agents/applications', [SuperAgentApplicationController::class, 'index'])->name('admin.super_agents.applications');
    Route::post('/super-agents/applications/{id}/approve', [SuperAgentApplicationController::class, 'approve'])->name('admin.super_agents.applications.approve');
    Route::post('/super-agents/applications/{id}/reject', [SuperAgentApplicationController::class, 'reject'])->name('admin.super_agents.applications.reject');

    // Super Agent Management — literal routes MUST come before the {id} wildcard
    Route::get('/super-agents', [SuperAgentManagementController::class, 'index'])->name('admin.super_agents.index');
    Route::post('/super-agents/promote', [SuperAgentManagementController::class, 'promote'])->name('admin.super_agents.promote');

    // Content Bonus Requests Management (before {id} wildcard)
    Route::get('/super-agents/content-requests', [ContentBonusController::class, 'index'])->name('admin.super_agents.content_requests');
    Route::get('/super-agents/content-requests/{id}', [ContentBonusController::class, 'show'])->name('admin.super_agents.content_requests.show');
    Route::post('/super-agents/content-requests/{id}/approve', [ContentBonusController::class, 'approve'])->name('admin.super_agents.content_requests.approve');
    Route::post('/super-agents/content-requests/{id}/reject', [ContentBonusController::class, 'reject'])->name('admin.super_agents.content_requests.reject');

    // Super Agent Challenges Management (before {id} wildcard)
    // 'as' => 'admin.super_agents' produces: admin.super_agents.challenges.index/create/store/edit/update/destroy
    Route::resource('super-agents/challenges', SuperAgentChallengeController::class, [
        'as'         => 'admin.super_agents',
        'parameters' => ['challenges' => 'id'],
    ]);
    Route::post('/super-agents/challenges/{id}/toggle', [SuperAgentChallengeController::class, 'toggle'])->name('admin.super_agents.challenges.toggle');

    // Super Agent Settings (before {id} wildcard)
    Route::get('/super-agents/settings', [SuperAgentSettingsController::class, 'index'])->name('admin.super_agents.settings');
    Route::post('/super-agents/settings', [SuperAgentSettingsController::class, 'update'])->name('admin.super_agents.settings.update');

    // Super Agent wildcard route — MUST be last to avoid swallowing literal paths above
    Route::get('/super-agents/{id}', [SuperAgentManagementController::class, 'show'])->name('admin.super_agents.show');
    Route::post('/super-agents/{id}/demote', [SuperAgentManagementController::class, 'demote'])->name('admin.super_agents.demote');
    Route::post('/super-agents/{id}/update-team-name', [SuperAgentManagementController::class, 'updateTeamName'])->name('admin.super_agents.update_team_name');
    Route::post('/super-agents/{id}/regenerate-code', [SuperAgentManagementController::class, 'regenerateCode'])->name('admin.super_agents.regenerate_code');
});

Route::fallback(function () {
    if (file_exists(public_path('index.html'))) {
        return file_get_contents(public_path('index.html'));
    }
    abort(404);
});
