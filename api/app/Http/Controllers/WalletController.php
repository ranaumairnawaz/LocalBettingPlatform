<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\DepositMethod;
use App\Models\MethodAccount;
use App\Models\WithdrawalMethod;
use App\Models\UserWithdrawalAccount;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function getMethods()
    {
        return DepositMethod::where('is_active', true)->get();
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'method_id' => 'required|exists:deposit_methods,id',
        ]);

        return DB::transaction(function () use ($request) {
            $account = MethodAccount::where('method_id', $request->method_id)
                ->where('is_active', true)
                ->where('receiving_limit', '>=', $request->amount)
                ->with('values')
                ->first();

            if (!$account) {
                return response()->json(['message' => 'No available accounts for this amount. Please try a different amount or method.'], 422);
            }

            $account->decrement('receiving_limit', $request->amount);

            $transaction = $request->user()->transactions()->create([
                'amount' => $request->amount,
                'type' => 'deposit',
                'method' => $account->method->name,
                'method_account_id' => $account->id,
                'status' => 'pending', // Waiting for user to send proof
            ]);

            return response()->json([
                'message' => 'Deposit request started.',
                'transaction' => $transaction,
                'account' => $account
            ]);
        });
    }

    public function confirmDeposit(Request $request, $id)
    {
        $request->validate([
            'reference' => 'nullable|string',
            'proof' => 'nullable|image|max:20480',
        ]);

        $transaction = Transaction::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $proof_path = null;
        if ($request->hasFile('proof')) {
            $proof_path = $request->file('proof')->store('proofs', 'public');
            $transaction->proof_image = $proof_path;
        }

        if ($request->filled('reference')) {
            $transaction->reference = $request->reference;
        }

        $transaction->save();

        return response()->json([
            'message' => 'Payment details confirmed. Admin will review soon.'
        ]);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:500',
            'withdrawal_method_id' => 'required|exists:withdrawal_methods,id',
        ]);

        $user = $request->user();
        $method = WithdrawalMethod::findOrFail($request->withdrawal_method_id);

        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        // Verify that the user has bound their account details for this method
        $boundAccount = $user->withdrawalAccounts()
            ->where('withdrawal_method_id', $method->id)
            ->first();

        if (!$boundAccount) {
            return response()->json([
                'message' => 'Account not bound! Please bind your ' . $method->name . ' account details before withdrawing.',
                'redirect_to_bind' => true
            ], 422);
        }

        return DB::transaction(function () use ($user, $method, $boundAccount, $request) {
            // Deduct balance immediately
            $user->decrement('balance', $request->amount);

            $transaction = $user->transactions()->create([
                'amount' => $request->amount,
                'type' => 'withdrawal',
                'method' => $method->name,
                'status' => 'pending',
                'withdrawal_details' => $boundAccount->account_details, // Snapshot details
            ]);

            return response()->json([
                'message' => 'Withdrawal request submitted.',
                'transaction' => $transaction,
                'new_balance' => $user->balance
            ]);
        });
    }

    public function transactions(Request $request)
    {
        $transactions = $request->user()->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    public function getWithdrawalMethods()
    {
        return response()->json(WithdrawalMethod::where('is_active', true)->get());
    }

    public function getUserWithdrawalAccounts(Request $request)
    {
        return response()->json($request->user()->withdrawalAccounts()->with('method')->get());
    }

    public function bindUserWithdrawalAccount(Request $request)
    {
        $request->validate([
            'withdrawal_method_id' => 'required|exists:withdrawal_methods,id',
            'account_details' => 'required|array',
        ]);

        $method = WithdrawalMethod::findOrFail($request->withdrawal_method_id);

        // Ensure all required fields are provided and non-empty
        foreach ($method->required_fields as $field) {
            if (empty($request->account_details[$field])) {
                return response()->json(['message' => "The field '{$field}' is required for " . $method->name . "."], 422);
            }
        }

        $account = UserWithdrawalAccount::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'withdrawal_method_id' => $request->withdrawal_method_id,
            ],
            [
                'account_details' => $request->account_details,
            ]
        );

        return response()->json([
            'message' => 'Account bound successfully.',
            'account' => $account->load('method')
        ]);
    }

    public function unbindUserWithdrawalAccount(Request $request, $id)
    {
        $account = $request->user()->withdrawalAccounts()->findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Account unbound successfully.']);
    }

    public function getTeamData(Request $request)
    {
        $user = $request->user();

        // Count and retrieve downline up to 5 levels
        $level1 = User::where('referred_by', $user->id)->get(['id', 'username', 'email', 'created_at']);
        $level1Ids = $level1->pluck('id');

        $level2 = $level1Ids->isNotEmpty() ? User::whereIn('referred_by', $level1Ids)->get(['id', 'username', 'email', 'created_at']) : collect();
        $level2Ids = $level2->pluck('id');

        $level3 = $level2Ids->isNotEmpty() ? User::whereIn('referred_by', $level2Ids)->get(['id', 'username', 'email', 'created_at']) : collect();
        $level3Ids = $level3->pluck('id');

        $level4 = $level3Ids->isNotEmpty() ? User::whereIn('referred_by', $level3Ids)->get(['id', 'username', 'email', 'created_at']) : collect();
        $level4Ids = $level4->pluck('id');

        $level5 = $level4Ids->isNotEmpty() ? User::whereIn('referred_by', $level4Ids)->get(['id', 'username', 'email', 'created_at']) : collect();

        // Map and mask user info for downstream privacy
        $maskUser = function ($u) {
            $username = $u->username;
            $len = strlen($username);
            $maskedUsername = $len > 4 ? substr($username, 0, 2) . str_repeat('*', $len - 4) . substr($username, -2) : substr($username, 0, 1) . str_repeat('*', $len - 1);
            
            $email = $u->email ?? '';
            $maskedEmail = 'N/A';
            if ($email && strpos($email, '@') !== false) {
                list($first, $domain) = explode('@', $email);
                $flen = strlen($first);
                $maskedFirst = $flen > 3 ? substr($first, 0, 1) . str_repeat('*', $flen - 2) . substr($first, -1) : substr($first, 0, 1) . str_repeat('*', $flen - 1);
                $maskedEmail = $maskedFirst . '@' . $domain;
            }

            return [
                'username' => $maskedUsername,
                'email' => $maskedEmail,
                'created_at' => $u->created_at->toIso8601String(),
            ];
        };

        $team = [
            'level1' => $level1->map($maskUser),
            'level2' => $level2->map($maskUser),
            'level3' => $level3->map($maskUser),
            'level4' => $level4->map($maskUser),
            'level5' => $level5->map($maskUser),
        ];

        // Referrer self status check
        $hasDeposited = Transaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->exists();

        // Calculate commissions
        $totalCommission = Transaction::where('user_id', $user->id)
            ->where('type', 'commission')
            ->where('status', 'completed')
            ->sum('amount');

        // Retrieve settings percentages for dynamic front display
        $commissions_percentages = [
            'level1' => floatval(SystemSetting::get('referral_commission_l1', 0)),
            'level2' => floatval(SystemSetting::get('referral_commission_l2', 0)),
            'level3' => floatval(SystemSetting::get('referral_commission_l3', 0)),
            'level4' => floatval(SystemSetting::get('referral_commission_l4', 0)),
            'level5' => floatval(SystemSetting::get('referral_commission_l5', 0)),
        ];

        return response()->json([
            'referral_code' => $user->referral_code,
            'has_deposited' => $hasDeposited,
            'total_commission' => $totalCommission,
            'percentages' => $commissions_percentages,
            'team' => $team,
        ]);
    }
}
