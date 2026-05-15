<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm()
    {
        // If already logged in, redirect to dashboard
        if (Session::has('admin_token')) {
            // return redirect('/dashboard');
            // For testing: just show message
            return view('auth.admin-login')->with('success', 'Already logged in as admin.');
        }
        
        return view('auth.admin-login');
    }
    
    /**
     * Handle admin login request using the API.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        try {
            // Call the actual API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->post('https://fati-api.alertaraqc.com/api/login', [
                    'email' => $request->email,
                    'password' => $request->password,
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Check if user is admin
                $userData = $data['data'] ?? [];
                $userRole = $userData['role'] ?? '';
                
                if (strtolower($userRole) !== 'admin') {
                    return back()
                        ->withInput($request->only('email'))
                        ->withErrors([
                            'email' => 'Access Denied: This account is not an Administrator.',
                        ]);
                }
                
                // Store admin data in session
                Session::put([
                    'admin_token' => $data['token'] ?? '',
                    'admin_data' => $userData,
                    'login_timestamp' => time(),
                ]);
                
                // return redirect()
                //     ->intended('/dashboard')
                //     ->with('login_success', true);
    
                // Check if AJAX request
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Login successful! Admin access granted.',
                        'redirect' => route('admin.dashboard'),
                        'csrf_token' => csrf_token()
                    ]);
                }
                
                // For testing: just show success message
                return view('auth.admin-login')->with('success', 'Login successful! Admin access granted.');
                
            } else {
                $errorMessage = $this->parseLoginError($response->body());
                
                // Check if AJAX request
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                }
                
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors([
                        'email' => $errorMessage,
                    ]);
            }
            
        } catch (\Exception $e) {
            // Check if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login failed: ' . $e->getMessage()
                ]);
            }
            
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Login failed: ' . $e->getMessage(),
                ]);
        }
    }
    
    /**
     * Log the admin out.
     */
    public function logout(Request $request)
    {
        Session::forget(['admin_token', 'admin_data', 'login_timestamp']);
        Session::invalidate();
        Session::regenerateToken();
        
        // return redirect('/');
        // For testing: just show message
        return view('auth.admin-login')->with('success', 'Logged out successfully.');
    }
    
    /**
     * Show the admin dashboard with real API data.
     */
    public function dashboard(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $adminData = Session::get('admin_data');

        try {
            $stats = $this->fetchDashboardStats($token);
            $recentOrders = $this->fetchRecentOrders($token);
        } catch (\Exception $e) {
            $stats = [
                'total_products' => 0,
                'total_orders' => 0,
                'revenue' => 0,
                'total_customers' => 0,
            ];
            $recentOrders = [];
        }

        return view('admin.dashboard', compact('stats', 'recentOrders', 'adminData'));
    }

    /**
     * Show private offers (private items).
     */
    public function privateOffers(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/items', [
                    'status' => 'private',
                ]);

            $items = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $items = [];
        }

        return view('admin.private-offers', compact('items'));
    }

    /**
     * Show acquired items.
     */
    public function acquiredItems(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/items', [
                    'status' => 'acquired',
                ]);

            $items = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $items = [];
        }

        return view('admin.acquired-items', compact('items'));
    }

    /**
     * Show public listings.
     */
    public function publicListings(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/items', [
                    'status' => 'public',
                ]);

            $items = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $items = [];
        }

        return view('admin.public-listings', compact('items'));
    }

    /**
     * Show reserved items.
     */
    public function reservedItems(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/items', [
                    'status' => 'reserved',
                ]);

            $items = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $items = [];
        }

        return view('admin.reserved-items', compact('items'));
    }

    /**
     * Show sold items.
     */
    public function soldItems(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/items', [
                    'status' => 'sold',
                ]);

            $items = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $items = [];
        }

        return view('admin.sold-items', compact('items'));
    }

    /**
     * Show student/user management page.
     */
    public function students(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/students');

            $students = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $students = [];
        }

        return view('admin.students', compact('students'));
    }

    /**
     * Show conversations/messaging page.
     */
    public function conversations(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        return view('admin.conversations');
    }

    /**
     * Show transactions page.
     */
    public function transactions(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $filter = $request->query('filter', '');

        try {
            $params = [];
            if ($filter) {
                $params['status'] = $filter;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/transactions', $params);

            $transactions = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $transactions = [];
        }

        return view('admin.transactions', compact('transactions'));
    }

    /**
     * Show reports and analytics page.
     */
    public function reports(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/reports');

            $reports = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $reports = [];
        }

        return view('admin.reports', compact('reports'));
    }

    /**
     * Show categories management page.
     */
    public function categories(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/categories');

            $categories = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $categories = [];
        }

        return view('admin.categories', compact('categories'));
    }

    /**
     * Show activity logs page.
     */
    public function activity(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/activity');

            $activities = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            $activities = [];
        }

        return view('admin.activity', compact('activities'));
    }

    /**
     * Show admin profile page.
     */
    public function profile(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $adminData = Session::get('admin_data');

        return view('admin.profile', compact('adminData'));
    }

    /**
     * Show settings page.
     */
    public function settings(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        return view('admin.settings');
    }
    
    /**
     * Fetch dashboard statistics from API.
     */
    private function fetchDashboardStats($token)
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])
            ->get('https://fati-api.alertaraqc.com/api/admin/dashboard/stats');
        
        if ($response->successful()) {
            return $response->json();
        }
        
        return [
            'total_products' => 248,
            'total_orders' => 1429,
            'revenue' => 28492.00,
            'total_customers' => 3847,
        ];
    }
    
    /**
     * Fetch recent orders from API.
     */
    private function fetchRecentOrders($token)
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])
            ->get('https://fati-api.alertaraqc.com/api/admin/orders/recent');
        
        if ($response->successful()) {
            return $response->json();
        }
        
        return [
            ['id' => 1234, 'customer' => 'Alice Johnson', 'product' => 'Organic Tomatoes', 'amount' => 24.99, 'status' => 'completed', 'date' => '2024-01-15'],
            ['id' => 1235, 'customer' => 'Bob Smith', 'product' => 'Fresh Lettuce', 'amount' => 12.50, 'status' => 'processing', 'date' => '2024-01-15'],
        ];
    }
    
    /**
     * Parse login error from API response.
     */
    private function parseLoginError($body)
    {
        $data = json_decode($body, true);
        
        if (isset($data['errors'])) {
            $errors = [];
            foreach ($data['errors'] as $field => $messages) {
                $errors = array_merge($errors, $messages);
            }
            return implode("\n", $errors);
        }
        
        if (isset($data['message'])) {
            return $data['message'];
        }
        
        return 'Login failed. Please try again.';
    }
}
