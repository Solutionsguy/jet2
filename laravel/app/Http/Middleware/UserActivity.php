<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class UserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Session::has('userlogin')) {
            $userSession = Session::get('userlogin');
            // Assuming userlogin session stores either User object or user ID
            $userId = is_object($userSession) ? $userSession->id : ($userSession['id'] ?? null);
            
            if ($userId) {
                User::where('id', $userId)->update(['last_seen' => now()]);
            }
        }

        return $next($request);
    }
}
