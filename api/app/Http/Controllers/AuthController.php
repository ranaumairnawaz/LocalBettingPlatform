<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|min:6',
            'referred_by_code' => 'nullable|exists:users,referral_code',
        ]);

        $referred_by = null;
        if ($request->referred_by_code) {
            $referrer = User::where('referral_code', $request->referred_by_code)->first();
            $referred_by = $referrer->id;
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referral_code' => strtoupper(Str::random(8)),
            'referred_by' => $referred_by,
            'balance' => 0,
        ]);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Invalid credentials.'],
            ]);
        }

        if ($user->is_banned) {
            throw ValidationException::withMessages([
                'username' => ['Your account has been banned. Please contact support.'],
            ]);
        }

        $user->last_login_ip = $request->ip();
        $user->save();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
