<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\GameLog;
use App\Models\User;
use App\Models\DepositMethod;
use App\Models\MethodAccount;
use App\Models\AccountValue;
use App\Models\Game;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WithdrawalMethod;
use App\Notifications\DepositApprovedNotification;
use App\Notifications\WithdrawalApprovedNotification;

class AdminController extends Controller
{
    public function dashboard()
    {
        $total_deposits = Transaction::where('type', 'deposit')->where('status', 'completed')->sum('amount');
        $total_withdrawals = Transaction::where('type', 'withdrawal')->where('status', 'completed')->sum('amount');

        // Profitability: House always wins a percentage
        $total_commission = GameLog::sum('commission_amount');
        $total_bets = GameLog::sum('bet_amount');
        $total_payouts = GameLog::sum('payout_amount');
        $gaming_profit = $total_bets - $total_payouts;

        return response()->json([
            'total_deposits' => $total_deposits,
            'total_withdrawals' => $total_withdrawals,
            'total_commission' => $total_commission,
            'gaming_profit' => $gaming_profit,
            'net_profit' => $gaming_profit + $total_commission,
            'active_users' => User::count(),
        ]);
    }

    public function pendingTransactions()
    {
        return Transaction::with('user')->where('status', 'pending')->orderBy('created_at', 'desc')->get();
    }

    public function updateTransaction(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:completed,rejected',
            'admin_note' => 'nullable|string',
        ]);

        $transaction = Transaction::findOrFail($id);

        return DB::transaction(function () use ($transaction, $request) {
            $transaction->status = $request->status;
            $transaction->admin_note = $request->admin_note;
            $transaction->save();

            if ($transaction->type === 'deposit') {
                if ($request->status === 'completed') {
                    $transaction->user->increment('balance', $transaction->amount);
                    $transaction->user->notify(new DepositApprovedNotification((float) $transaction->amount));
                    // Distribute referral commission up to 5 levels
                    $this->distributeReferralCommission($transaction->user, $transaction->amount);
                } elseif ($request->status === 'rejected') {
                    // Restore the account's receiving limit
                    if ($transaction->method_account_id) {
                        $transaction->methodAccount->increment('receiving_limit', $transaction->amount);
                    }
                }
            }

            if ($transaction->type === 'withdrawal' && $request->status === 'rejected') {
                $transaction->user->increment('balance', $transaction->amount);
            }

            if ($transaction->type === 'withdrawal' && $request->status === 'completed') {
                $transaction->user->notify(new WithdrawalApprovedNotification((float) $transaction->amount));
            }

            return response()->json(['message' => 'Transaction updated successfully.']);
        });
    }

    protected function distributeReferralCommission(User $depositor, $depositAmount)
    {
        $currentUser = $depositor;

        for ($level = 1; $level <= 5; $level++) {
            $referrerId = $currentUser->referred_by;
            if (!$referrerId) {
                break;
            }

            $referrer = User::find($referrerId);
            if (!$referrer) {
                break;
            }

            // Check active status: Referrer must have made at least one lifetime completed deposit
            $hasDeposited = Transaction::where('user_id', $referrer->id)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->exists();

            if ($hasDeposited) {
                // Determine commission rate based on Super Agent status
                if ($referrer->is_super_agent) {
                    // Use Super Agent tiered rate based on rank
                    $rank = $referrer->super_agent_rank ?? 'bronze';
                    $percentageKey = "super_agent_commission_{$rank}_l" . $level;
                } else {
                    // Use regular referral rate
                    $percentageKey = "referral_commission_l" . $level;
                }

                $percentage = floatval(SystemSetting::get($percentageKey, 0));

                if ($percentage > 0) {
                    $commissionAmount = ($depositAmount * $percentage) / 100;
                    if ($commissionAmount > 0) {
                        $referrer->increment('balance', $commissionAmount);

                        // Create transaction record
                        $transaction = Transaction::create([
                            'user_id' => $referrer->id,
                            'amount' => $commissionAmount,
                            'type' => 'commission',
                            'method' => 'Team Commission',
                            'status' => 'completed',
                            'reference' => 'COMM_L' . $level . '_' . $depositor->id . '_' . time() . '_' . rand(1000, 9999),
                            'admin_note' => "Level {$level} referral commission from deposit of {$depositor->username} (Rs. {$depositAmount})",
                        ]);

                        // If Super Agent, also log to super_agent_commissions table
                        if ($referrer->is_super_agent) {
                            \App\Models\SuperAgentCommission::create([
                                'super_agent_id' => $referrer->id,
                                'source_user_id' => $depositor->id,
                                'commission_type' => 'deposit_commission',
                                'referral_level' => $level,
                                'commission_amount' => $commissionAmount,
                                'transaction_id' => $transaction->id,
                                'is_paid' => true,
                            ]);

                            // Dispatch event for real-time notification
                            event(new \App\Events\SuperAgentEarningUpdated($referrer->id, $commissionAmount, 'deposit'));
                        }
                    }
                }
            }

            // Go to next parent in hierarchy
            $currentUser = $referrer;
        }
    }

    // Blade View Methods
    public function bladeLogin()
    {
        return view('admin.login');
    }

    public function bladeLoginSubmit(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (auth()->attempt($credentials)) {
            if (auth()->user()->is_admin) {
                $request->session()->regenerate();
                return redirect()->route('admin.dashboard');
            }
            auth()->logout();
            return back()->withErrors(['username' => 'Access denied. Admins only.']);
        }

        return back()->withErrors(['username' => 'Invalid credentials.']);
    }

    public function bladeLogout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function bladeDashboard()
    {
        $total_bets = GameLog::sum('bet_amount');
        $total_payouts = GameLog::sum('payout_amount');
        $total_commission = GameLog::sum('commission_amount');
        $total_profit = $total_bets - $total_payouts + $total_commission;
        $profit_percentage = $total_bets > 0 ? round(($total_profit / $total_bets) * 100, 2) : 0;

        $stats = [
            'total_deposits' => Transaction::where('type', 'deposit')->where('status', 'completed')->sum('amount'),
            'total_withdrawals' => Transaction::where('type', 'withdrawal')->where('status', 'completed')->sum('amount'),
            'pending_deposits' => Transaction::where('type', 'deposit')->where('status', 'pending')->count(),
            'pending_withdrawals' => Transaction::where('type', 'withdrawal')->where('status', 'pending')->count(),
            'total_profit' => $total_profit,
            'profit_percentage' => $profit_percentage,
            'total_commission' => $total_commission,
            'active_users' => User::count(),
        ];

        // 7-day dynamic financial stats for Chart.js
        $chart_labels = [];
        $chart_deposits = [];
        $chart_withdrawals = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chart_labels[] = now()->subDays($i)->format('d M');
            
            $chart_deposits[] = Transaction::where('type', 'deposit')
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');
                
            $chart_withdrawals[] = Transaction::where('type', 'withdrawal')
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');
        }

        return view('admin.dashboard', compact('stats', 'chart_labels', 'chart_deposits', 'chart_withdrawals'));
    }

    public function bladeTransactions(Request $request)
    {
        $query = Transaction::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('method', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        return view('admin.transactions', compact('transactions'));
    }

    public function bladePendingDeposits(Request $request)
    {
        $query = Transaction::with('user')
            ->where('type', 'deposit')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('method', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        return view('admin.pending_deposits', compact('transactions'));
    }

    public function bladePendingWithdrawals(Request $request)
    {
        $query = Transaction::with('user')
            ->where('type', 'withdrawal')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('method', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        return view('admin.pending_withdrawals', compact('transactions'));
    }

    public function bladeFraudDetect()
    {
        $duplicateIps = User::select('last_login_ip', DB::raw('count(*) as ip_count'))
            ->whereNotNull('last_login_ip')
            ->groupBy('last_login_ip')
            ->havingRaw('count(*) > 1')
            ->orderBy('ip_count', 'desc')
            ->get();

        $ipGroups = [];
        foreach ($duplicateIps as $item) {
            $ip = $item->last_login_ip;
            $users = User::where('last_login_ip', $ip)
                ->orderBy('created_at', 'desc')
                ->get();
                
            $ipGroups[] = [
                'ip' => $ip,
                'count' => $item->ip_count,
                'users' => $users
            ];
        }

        return view('admin.fraud_detect', compact('ipGroups'));
    }

    public function bladeIpBatchBan(Request $request)
    {
        $request->validate([
            'ip' => 'required|string',
        ]);

        $ip = $request->ip;

        $updated = User::where('last_login_ip', $ip)
            ->where('is_admin', false)
            ->update(['is_banned' => true]);

        return back()->with('success', "Successfully banned {$updated} user(s) matching IP {$ip}.");
    }

    public function bladeUpdateTransaction(Request $request, $id)
    {
        // Re-use logic from API but redirect back
        $this->updateTransaction($request, $id);
        return back()->with('success', 'Transaction updated.');
    }

    public function bladeDepositMethods()
    {
        $methods = DepositMethod::with(['accounts.values'])->get();
        return view('admin.deposit_methods', compact('methods'));
    }

    public function bladeStoreDepositMethod(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        DepositMethod::create($request->all());
        return back()->with('success', 'Method added successfully.');
    }

    public function bladeStoreMethodAccount(Request $request, $method_id)
    {
        $request->validate(['receiving_limit' => 'required|numeric']);
        MethodAccount::create([
            'method_id' => $method_id,
            'receiving_limit' => $request->receiving_limit,
            'is_active' => true,
        ]);
        return back()->with('success', 'Account added successfully.');
    }

    public function bladeStoreAccountValue(Request $request, $account_id)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
        ]);
        AccountValue::create([
            'account_id' => $account_id,
            'key' => $request->key,
            'value' => $request->value,
        ]);
        return back()->with('success', 'Detail added successfully.');
    }

    // --- Users Management ---
    public function bladeUsers()
    {
        $users = User::withCount(['transactions', 'gameLogs'])->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function bladeUserShow($id)
    {
        $user = User::with([
            'transactions' => function ($q) {
                $q->latest()->take(20);
            },
            'gameLogs' => function ($q) {
                $q->latest()->take(20);
            }
        ])->findOrFail($id);

        $total_won = GameLog::where('user_id', $id)->where('result', 'win')->sum('payout_amount');
        $total_lost = GameLog::where('user_id', $id)->where('result', 'lose')->sum('bet_amount');

        // 5-Level downline counts
        $level1Ids = User::where('referred_by', $id)->pluck('id');
        $level2Ids = $level1Ids->isNotEmpty() ? User::whereIn('referred_by', $level1Ids)->pluck('id') : collect();
        $level3Ids = $level2Ids->isNotEmpty() ? User::whereIn('referred_by', $level2Ids)->pluck('id') : collect();
        $level4Ids = $level3Ids->isNotEmpty() ? User::whereIn('referred_by', $level3Ids)->pluck('id') : collect();
        $level5Ids = $level4Ids->isNotEmpty() ? User::whereIn('referred_by', $level4Ids)->pluck('id') : collect();

        $team_stats = [
            'l1' => $level1Ids->count(),
            'l2' => $level2Ids->count(),
            'l3' => $level3Ids->count(),
            'l4' => $level4Ids->count(),
            'l5' => $level5Ids->count(),
            'total' => $level1Ids->count() + $level2Ids->count() + $level3Ids->count() + $level4Ids->count() + $level5Ids->count()
        ];

        return view('admin.users.show', compact('user', 'total_won', 'total_lost', 'team_stats'));
    }

    public function bladeUserBan(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->is_banned = !$user->is_banned;
        $user->save();
        $msg = $user->is_banned ? 'User has been banned.' : 'User has been unbanned.';
        return back()->with('success', $msg);
    }

    // --- Games Management ---
    public function bladeGames()
    {
        $games = Game::all();
        return view('admin.games.index', compact('games'));
    }

    public function bladeGameShow($id)
    {
        $game = Game::findOrFail($id);

        // Performance Analytics
        $total_bet = GameLog::where('game_id', $game->id)->sum('bet_amount');
        $total_won = GameLog::where('game_id', $game->id)->sum('payout_amount');
        $total_commission = GameLog::where('game_id', $game->id)->sum('commission_amount');
        $net_balance = $total_bet - $total_won;

        $specific_settings = [];
        if ($game->slug === 'teen_patti') {
            $bootsDefault = '10, 50, 100, 500, 1000';
            $bootsRaw = SystemSetting::get('three_patti_boots');
            $specific_settings['three_patti_boots'] = $bootsRaw ? implode(', ', json_decode($bootsRaw, true) ?? []) : $bootsDefault;
        }

        return view('admin.games.show', compact('game', 'total_bet', 'total_won', 'total_commission', 'net_balance', 'specific_settings'));
    }

    public function bladeUpdateGame(Request $request, $id)
    {
        $game = Game::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string',
            'min_bet' => 'required|numeric',
            'max_bet' => 'required|numeric',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'required|boolean',
            'is_featured' => 'nullable|boolean',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'guidelines' => 'nullable|string'
        ]);

        if ($game->slug === 'teen_patti' && $request->has('three_patti_boots')) {
            $bootsValue = $request->input('three_patti_boots');
            $valuesArray = array_filter(array_map('trim', explode(',', $bootsValue)), 'is_numeric');
            $valuesArray = array_values(array_map('floatval', $valuesArray));
            SystemSetting::updateOrCreate(['key' => 'three_patti_boots'], ['value' => json_encode($valuesArray), 'type' => 'array']);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('games', 'public');
            $data['image'] = $path;
        }

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('games', 'public');
            $data['thumbnail'] = $path;
        }

        $game->update($data);
        return back()->with('success', 'Game updated successfully.');
    }

    // Quick one-click enable/disable toggle
    public function bladeToggleGame($id)
    {
        $game = Game::findOrFail($id);
        $game->is_active = !$game->is_active;
        $game->save();
        $status = $game->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Game '{$game->name}' has been {$status}.");
    }

    // Quick one-click featured toggle
    public function bladeToggleFeatured($id)
    {
        $game = Game::findOrFail($id);
        $game->is_featured = !$game->is_featured;
        $game->save();
        $status = $game->is_featured ? 'featured' : 'standard';
        return back()->with('success', "Game '{$game->name}' is now set as {$status}.");
    }

    // --- Settings Management ---
    public function bladeSettings()
    {
        $settings = SystemSetting::all()->pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    public function bladeUpdateSettings(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
        ]);

        foreach ($request->except(['_token', 'logo']) as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'text']);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            SystemSetting::updateOrCreate(['key' => 'site_logo'], ['value' => $path, 'type' => 'image']);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    public function bladeWithdrawalMethods()
    {
        $methods = WithdrawalMethod::all();
        return view('admin.withdrawal_methods', compact('methods'));
    }

    public function bladeStoreWithdrawalMethod(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'required_fields' => 'required|string', // Comma separated fields
        ]);

        $fields = array_filter(array_map('trim', explode(',', $request->required_fields)));

        WithdrawalMethod::create([
            'name' => $request->name,
            'required_fields' => array_values($fields),
            'is_active' => true,
        ]);

        return back()->with('success', 'Withdrawal method added successfully.');
    }

    public function bladeToggleWithdrawalMethod($id)
    {
        $method = WithdrawalMethod::findOrFail($id);
        $method->is_active = !$method->is_active;
        $method->save();
        $status = $method->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Withdrawal method '{$method->name}' has been {$status}.");
    }

    public function bladeDeleteWithdrawalMethod($id)
    {
        $method = WithdrawalMethod::findOrFail($id);
        $method->delete();
        return back()->with('success', "Withdrawal method deleted successfully.");
    }

    public function bladeAdjustBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'action' => 'required|in:credit,debit',
            'admin_note' => 'required|string',
        ]);

        $user = User::findOrFail($id);
        $amount = floatval($request->amount);

        return DB::transaction(function () use ($user, $amount, $request) {
            if ($request->action === 'credit') {
                $user->increment('balance', $amount);

                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'type' => 'deposit',
                    'method' => 'Admin Adjustment',
                    'status' => 'completed',
                    'reference' => 'ADJ_CR_' . time() . '_' . rand(1000, 9999),
                    'admin_note' => 'Admin Credit: ' . $request->admin_note,
                ]);
            } else {
                if ($user->balance < $amount) {
                    return back()->withErrors(['amount' => 'User balance is insufficient for this deduction.']);
                }
                $user->decrement('balance', $amount);

                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'type' => 'withdrawal',
                    'method' => 'Admin Adjustment',
                    'status' => 'completed',
                    'reference' => 'ADJ_DR_' . time() . '_' . rand(1000, 9999),
                    'admin_note' => 'Admin Debit: ' . $request->admin_note,
                ]);
            }

            return back()->with('success', 'User balance adjusted successfully.');
        });
    }

    // --- Tournament Management ---
    public function bladeTournamentScheduler()
    {
        return view('admin.tournaments.scheduler');
    }

    public function bladeTournamentAudit()
    {
        return view('admin.tournaments.audit');
    }

    public function bladeGetGamesJSON()
    {
        return response()->json(Game::all());
    }
}
