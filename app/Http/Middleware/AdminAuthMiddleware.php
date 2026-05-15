<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdminAuthMiddleware
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
        // Check if admin is logged in via session
        if (!Session::has('admin_token') || !Session::has('admin_data')) {
            return redirect('/');
        }

        // Check if session is expired (24 hours)
        $loginTimestamp = Session::get('login_timestamp');
        if ($loginTimestamp && (time() - $loginTimestamp) > 86400) { // 24 hours
            Session::forget(['admin_token', 'admin_data', 'login_timestamp']);
            return redirect('/')
                ->with('error', 'Session expired. Please login again.');
        }

        return $next($request);
    }
}
