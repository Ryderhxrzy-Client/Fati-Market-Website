@extends('layouts.admin-dashboard')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Products</p>
                    <div class="stat-value">{{ $stats['total_products'] ?? 248 }}</div>
                    <p class="text-gray-500 text-xs">Active in store</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fas fa-box text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Orders</p>
                    <div class="stat-value">{{ $stats['total_orders'] ?? 1429 }}</div>
                    <p class="text-gray-500 text-xs">This month</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Revenue</p>
                    <div class="stat-value">${{ number_format($stats['revenue'] ?? 28492, 0) }}</div>
                    <p class="text-gray-500 text-xs">This month</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Customers</p>
                    <div class="stat-value">{{ $stats['total_customers'] ?? 3847 }}</div>
                    <p class="text-gray-500 text-xs">Registered users</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Recent Orders</h3>
                <p class="text-sm text-gray-500">Latest orders from customers</p>
            </div>
            <a href="{{ route('admin.transactions') }}" class="text-green-600 hover:text-green-700 text-sm font-medium">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentOrders ?? [] as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ $order['id'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $order['customer'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $order['product'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">${{ $order['amount'] ?? 0 }}</td>
                            <td class="px-6 py-4 text-sm">
                                @php
                                    $status = strtolower($order['status'] ?? 'pending');
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-800',
                                        'processing' => 'bg-yellow-100 text-yellow-800',
                                        'pending' => 'bg-gray-100 text-gray-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $color = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $color }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $order['date'] ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2 opacity-50"></i>
                                <p class="mt-2">No recent orders</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Quick Actions</h3>
                <i class="fas fa-bolt text-yellow-500"></i>
            </div>
            <div class="space-y-2">
                <a href="{{ route('admin.students') }}" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                    <span class="text-gray-700">Manage Users</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                </a>
                <a href="{{ route('admin.acquired-items') }}" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                    <span class="text-gray-700">Manage Items</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                </a>
                <a href="{{ route('admin.conversations') }}" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                    <span class="text-gray-700">View Messages</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">System Status</h3>
                <i class="fas fa-heartbeat text-green-500"></i>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">API Connection</span>
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">Database</span>
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">Cache</span>
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
