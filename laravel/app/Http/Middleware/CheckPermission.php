<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!has_permission($permission)) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 0,
                    'title' => 'Forbidden',
                    'message' => 'You do not have permission to perform this action.'
                ], 403);
            }
            
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
