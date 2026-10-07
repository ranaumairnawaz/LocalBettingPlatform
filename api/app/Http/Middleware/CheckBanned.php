<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        
        $bannedIpExists = User::where('last_login_ip', $ip)->where('is_banned', true)->exists();
        if ($bannedIpExists) {
            return response()->json(['message' => 'Your IP address is banned from accessing this site.'], 403);
        }

        if (auth()->check() && auth()->user()->is_banned) {
            if ($request->user() && $request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            }
            return response()->json(['message' => 'Your account has been banned.'], 403);
        }

        return $next($request);
    }
}
