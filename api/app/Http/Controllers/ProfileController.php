<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function logins(Request $request)
    {
        $user = $request->user();
        
        // Use the personal_access_tokens as a proxy for logins
        $tokens = PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'name', 'created_at', 'last_used_at']);

        return response()->json([
            'logins' => $tokens
        ]);
    }
}
