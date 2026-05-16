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
        // Check if admin is logged in via session using get method
        $token = session()->get('admin_token');
        $adminData = session()->get('admin_data');

        if (empty($token) || empty($adminData)) {
            return redirect('/');
        }

        // Check if session is expired (24 hours)
        $loginTimestamp = session()->get('login_timestamp');
        if ($loginTimestamp && (time() - $loginTimestamp) > 86400) {
            session()->forget(['admin_token', 'admin_data', 'login_timestamp', 'admin_profile_picture', 'admin_first_name', 'admin_last_name']);
            return redirect('/')
                ->with('error', 'Session expired. Please login again.');
        }

        return $next($request);
    }
}
