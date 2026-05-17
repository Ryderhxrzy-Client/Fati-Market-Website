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
            return redirect()->route('admin.dashboard');
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

            // Log the response for debugging
            \Log::info('Login API response', [
                'status' => $response->status(),
                'is_successful' => $response->successful(),
                'content_type' => $response->header('Content-Type'),
                'body_preview' => substr($response->body(), 0, 200)
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
                $token = $userData['token'] ?? '';

                if (empty($token)) {
                    \Log::error('Token is empty from API response', ['data' => $data]);
                    return back()
                        ->withInput($request->only('email'))
                        ->withErrors(['email' => 'Login failed: Invalid response from server']);
                }

                // Use individual session() calls instead of array
                session()->put('admin_token', $token);
                session()->put('admin_data', $userData);
                session()->put('admin_profile_picture', $userData['profile_picture'] ?? '');
                session()->put('admin_first_name', $userData['first_name'] ?? '');
                session()->put('admin_last_name', $userData['last_name'] ?? '');
                session()->put('login_timestamp', time());

                \Log::info('Admin login successful', [
                    'token_length' => strlen($token),
                    'user_id' => $userData['user_id'] ?? null,
                    'session_token_stored' => session('admin_token') ? 'YES' : 'NO',
                    'session_keys' => array_keys(session()->all())
                ]);

                // Check if AJAX request
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Login successful! Admin access granted.',
                        'redirect' => route('admin.dashboard'),
                        'csrf_token' => csrf_token()
                    ]);
                }

                // Redirect to dashboard
                return redirect()->route('admin.dashboard')
                    ->with('success', 'Login successful! Welcome to the admin dashboard.');
                
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

        return redirect('/')
            ->with('success', 'Logged out successfully.');
    }
    
    /**
     * Show the admin dashboard with real API data.
     */
    public function dashboard(Request $request)
    {
        $token = session()->get('admin_token');
        $adminData = session()->get('admin_data');

        if (empty($token) || empty($adminData)) {
            return redirect('/');
        }

        try {
            $stats = $this->fetchDashboardStats($token);
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());
            $stats = $this->getDefaultStats();
        }

        return view('admin.dashboard', compact('stats', 'adminData'));
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

            // Log first item to see structure
            if (!empty($items)) {
                \Log::info('Private offers API response structure', [
                    'first_item' => $items[0] ?? [],
                    'total_items' => count($items),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Private offers error: ' . $e->getMessage());
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
     * Show transaction history page.
     */
    public function transactionHistory(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $transactions = [];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/transactions');

            if ($response->successful()) {
                $apiTransactions = $response->json()['data'] ?? [];
                
                // Transform API data to match view expectations
                $transactions = array_map(function ($txn) {
                    return [
                        'transaction_id' => $txn['transaction_id'] ?? null,
                        'item_title' => $txn['item']['title'] ?? 'N/A',
                        'buyer_email' => $txn['buyer']['email'] ?? 'N/A',
                        'seller_email' => $txn['seller']['email'] ?? 'N/A',
                        'payment_method' => $txn['payment_method'] ?? 'N/A',
                        'status' => $txn['status'] ?? 'pending',
                        'points_used' => $txn['points_used'] ?? 0,
                        'transaction_date' => $txn['transaction_date'] ?? null,
                    ];
                }, $apiTransactions);
            }
        } catch (\Exception $e) {
            \Log::error('Transaction history fetch error: ' . $e->getMessage());
            $transactions = [];
        }

        return view('admin.transactions.history', compact('transactions'));
    }

    /**
     * Show points given page.
     */
    public function pointsGiven(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $transactions = [];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/points/given');

            $transactions = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            \Log::error('Points given fetch error: ' . $e->getMessage());
            $transactions = [];
        }

        return view('admin.transactions.points-given', compact('transactions'));
    }

    /**
     * Show points received page.
     */
    public function pointsReceived(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $transactions = [];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/points/received');

            $transactions = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            \Log::error('Points received fetch error: ' . $e->getMessage());
            $transactions = [];
        }

        return view('admin.transactions.points-received', compact('transactions'));
    }

    /**
     * Show cash transactions page.
     */
    public function cashTransactions(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $transactions = [];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/transactions/cash');

            $transactions = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            \Log::error('Cash transactions fetch error: ' . $e->getMessage());
            $transactions = [];
        }

        return view('admin.transactions.cash', compact('transactions'));
    }

    /**
     * Show trade transactions page.
     */
    public function tradeTransactions(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $transactions = [];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/transactions/trade');

            $transactions = $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (\Exception $e) {
            \Log::error('Trade transactions fetch error: ' . $e->getMessage());
            $transactions = [];
        }

        return view('admin.transactions.trade', compact('transactions'));
    }

    /**
     * Show profit summary page.
     */
    public function profitSummary(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $transactions = [];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/transactions/profit-summary');

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                // Map API data to view field names
                $transactions = [
                    'total_profit' => (int)($data['total_profit_points'] ?? 0),
                    'monthly_profit' => (int)($data['monthly_profit_points'] ?? 0),
                    'completed_transactions' => (int)($data['completed_transactions'] ?? 0),
                    'avg_per_transaction' => (float)($data['average_profit_per_transaction'] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Profit summary fetch error: ' . $e->getMessage());
            $transactions = [
                'total_profit' => 0,
                'monthly_profit' => 0,
                'completed_transactions' => 0,
                'avg_per_transaction' => 0,
            ];
        }

        return view('admin.transactions.profit', compact('transactions'));
    }

    /**
     * Show items acquired report.
     */
    public function itemsAcquiredReport(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $reportData = ['total_acquired' => 0, 'sales' => []];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/reports/sales');

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                $reportData['total_acquired'] = $data['total_items_acquired'] ?? 0;
                
                // Sales table
                $recentSales = $data['recent_sales'] ?? [];
                $reportData['sales'] = array_map(function ($sale) {
                    return [
                        'transaction_id' => $sale['transaction_id'] ?? null,
                        'item_name' => $sale['item']['title'] ?? 'N/A',
                        'buyer_email' => $sale['buyer']['email'] ?? 'N/A',
                        'seller_email' => $sale['seller']['email'] ?? 'N/A',
                        'points_used' => $sale['points_used'] ?? 0,
                        'status' => $sale['status'] ?? 'pending',
                    ];
                }, $recentSales);
            }
        } catch (\Exception $e) {
            \Log::error('Items acquired report fetch error: ' . $e->getMessage());
            $reportData = ['total_acquired' => 0, 'sales' => []];
        }

        return view('admin.reports.items-acquired', compact('reportData'));
    }

    /**
     * Show items sold report.
     */
    public function itemsSoldReport(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $reportData = ['total_sold' => 0, 'sales' => []];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/reports/sales');

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                $reportData['total_sold'] = $data['total_items_sold'] ?? 0;
                
                // Sales table
                $recentSales = $data['recent_sales'] ?? [];
                $reportData['sales'] = array_map(function ($sale) {
                    return [
                        'transaction_id' => $sale['transaction_id'] ?? null,
                        'item_name' => $sale['item']['title'] ?? 'N/A',
                        'buyer_email' => $sale['buyer']['email'] ?? 'N/A',
                        'seller_email' => $sale['seller']['email'] ?? 'N/A',
                        'points_used' => $sale['points_used'] ?? 0,
                        'status' => $sale['status'] ?? 'pending',
                    ];
                }, $recentSales);
            }
        } catch (\Exception $e) {
            \Log::error('Items sold report fetch error: ' . $e->getMessage());
            $reportData = ['total_sold' => 0, 'sales' => []];
        }

        return view('admin.reports.items-sold', compact('reportData'));
    }

    /**
     * Show total profit report.
     */
    public function totalProfitReport(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $reportData = [
            'total_profit_points' => 0,
            'monthly_profit_points' => 0,
            'completed_transactions' => 0,
            'average_profit_per_transaction' => 0,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/transactions/profit-summary');

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                $reportData = [
                    'total_profit_points' => (int)($data['total_profit_points'] ?? 0),
                    'monthly_profit_points' => (int)($data['monthly_profit_points'] ?? 0),
                    'completed_transactions' => (int)($data['completed_transactions'] ?? 0),
                    'average_profit_per_transaction' => (float)($data['average_profit_per_transaction'] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Total profit report fetch error: ' . $e->getMessage());
            $reportData = [
                'total_profit_points' => 0,
                'monthly_profit_points' => 0,
                'completed_transactions' => 0,
                'average_profit_per_transaction' => 0,
            ];
        }

        return view('admin.reports.total-profit', compact('reportData'));
    }

    /**
     * Show profit report.
     */
    public function profitReport(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $reportData = ['total_markup' => 0, 'monthly_profit' => [], 'top_items' => []];

        try {
            // Fetch profit summary
            $profitResponse = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/transactions/profit-summary');

            if ($profitResponse->successful()) {
                $profitData = $profitResponse->json()['data'] ?? [];
                $reportData['total_markup'] = (int)($profitData['total_profit_points'] ?? 0);
            }

            // Fetch sales report for monthly profit
            $salesResponse = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/reports/sales');

            if ($salesResponse->successful()) {
                $salesData = $salesResponse->json()['data'] ?? [];
                
                // Map monthly sales data
                $reportData['monthly_profit'] = array_map(function ($sale) {
                    return [
                        'month' => $sale['month'] ?? 'N/A',
                        'count' => $sale['count'] ?? 0,
                    ];
                }, $salesData['sales_by_month'] ?? []);

                // Map recent sales as top profitable items
                $recentSales = $salesData['recent_sales'] ?? [];
                $reportData['top_items'] = array_map(function ($sale) {
                    return [
                        'item_name' => $sale['item']['title'] ?? 'N/A',
                        'markup_points' => $sale['item']['markup_points'] ?? 0,
                        'seller_email' => $sale['seller']['email'] ?? 'N/A',
                    ];
                }, $recentSales);
            }
        } catch (\Exception $e) {
            \Log::error('Profit report fetch error: ' . $e->getMessage());
            $reportData = ['total_markup' => 0, 'monthly_profit' => [], 'top_items' => []];
        }

        return view('admin.reports.profit', compact('reportData'));
    }

    /**
     * Show categories report.
     */
    public function categoriesReport(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $reportData = ['most_sold' => [], 'categories' => []];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/reports/categories');

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                // Most sold category
                $mosSold = $data['most_sold_category'] ?? [];
                $reportData['most_sold'] = [
                    'category_name' => $mosSold['category_name'] ?? 'N/A',
                    'items_sold' => $mosSold['items_sold'] ?? 0,
                    'total_markup_profit' => $mosSold['total_markup_profit'] ?? 0,
                ];
                
                // All categories
                $reportData['categories'] = array_map(function ($cat) {
                    return [
                        'category_name' => $cat['category_name'] ?? 'N/A',
                        'items_sold' => $cat['items_sold'] ?? 0,
                        'total_markup_profit' => $cat['total_markup_profit'] ?? 0,
                        'average_markup_per_item' => $cat['average_markup_per_item'] ?? 0,
                    ];
                }, $data['category_sales'] ?? []);
            }
        } catch (\Exception $e) {
            \Log::error('Categories report fetch error: ' . $e->getMessage());
            $reportData = ['most_sold' => [], 'categories' => []];
        }

        return view('admin.reports.categories', compact('reportData'));
    }

    /**
     * Show users report.
     */
    public function usersReport(Request $request)
    {
        if (!Session::has('admin_token')) {
            return redirect('/');
        }

        $token = Session::get('admin_token');
        $reportData = [
            'active_users' => 0,
            'total_students' => 0,
            'top_buyers' => [],
            'top_sellers' => [],
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/reports/users');

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                $reportData['active_users'] = $data['active_users'] ?? 0;
                $reportData['total_students'] = $data['total_students'] ?? 0;
                
                // Map top buyers
                $reportData['top_buyers'] = array_slice(array_map(function ($buyer) {
                    return [
                        'user_id' => $buyer['user_id'] ?? null,
                        'email' => $buyer['email'] ?? 'N/A',
                        'wallet_points' => $buyer['wallet_points'] ?? 0,
                        'transaction_count' => $buyer['transactions_as_buyer_count'] ?? 0,
                    ];
                }, $data['top_buyers'] ?? []), 0, 5);
                
                // Map top sellers
                $reportData['top_sellers'] = array_slice(array_map(function ($seller) {
                    return [
                        'user_id' => $seller['user_id'] ?? null,
                        'email' => $seller['email'] ?? 'N/A',
                        'wallet_points' => $seller['wallet_points'] ?? 0,
                        'transaction_count' => $seller['transactions_as_seller_count'] ?? 0,
                    ];
                }, $data['top_sellers'] ?? []), 0, 5);
            }
        } catch (\Exception $e) {
            \Log::error('Users report fetch error: ' . $e->getMessage());
            $reportData = [
                'active_users' => 0,
                'total_students' => 0,
                'top_buyers' => [],
                'top_sellers' => [],
            ];
        }

        return view('admin.reports.users', compact('reportData'));
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
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])
                ->get('https://fati-api.alertaraqc.com/api/admin/dashboard');

            if ($response->successful()) {
                $data = $response->json();
                $dataObj = $data['data'] ?? [];

                return [
                    'users' => $dataObj['users'] ?? [],
                    'items' => $dataObj['items'] ?? [],
                    'recent_activities' => $dataObj['recent_activities'] ?? [],
                ];
            }

            return $this->getDefaultStats();
        } catch (\Exception $e) {
            \Log::error('Dashboard stats fetch error: ' . $e->getMessage());
            return $this->getDefaultStats();
        }
    }

    private function getDefaultStats()
    {
        return [
            'users' => [
                'total_students' => 0,
                'active_students' => 0,
                'pending_students' => 0,
                'verified_students' => 0,
            ],
            'items' => [
                'total_items' => 0,
                'private_items' => 0,
                'public_items' => 0,
                'acquired_items' => 0,
                'reserved_items' => 0,
                'sold_items' => 0,
            ],
            'recent_activities' => [],
        ];
    }
    
    /**
     * Fetch recent orders from API.
     */
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
