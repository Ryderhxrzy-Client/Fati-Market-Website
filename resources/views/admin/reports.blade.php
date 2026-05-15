@extends('layouts.admin-dashboard')

@section('title', 'Report & Analytics')

@section('content')
<div class="space-y-6">
    <!-- Report Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Revenue</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">$28,492</p>
                    <p class="text-xs text-green-600 mt-2">+12.5% from last month</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">1,429</p>
                    <p class="text-xs text-green-600 mt-2">+8.3% from last month</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Active Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">3,847</p>
                    <p class="text-xs text-green-600 mt-2">+5.2% from last month</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Conversion Rate</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">3.24%</p>
                    <p class="text-xs text-orange-600 mt-2">-0.8% from last month</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-chart-line text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Revenue Trend</h3>
            <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                <p class="text-gray-500">Chart placeholder - Revenue over time</p>
            </div>
        </div>

        <!-- Orders Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Orders by Category</h3>
            <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                <p class="text-gray-500">Chart placeholder - Orders distribution</p>
            </div>
        </div>
    </div>

    <!-- Top Products & Users -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Products -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Top Products</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm text-gray-900">Organic Tomatoes</span>
                    <span class="text-sm font-semibold text-green-600">245 sold</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm text-gray-900">Fresh Lettuce</span>
                    <span class="text-sm font-semibold text-green-600">189 sold</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm text-gray-900">Green Peppers</span>
                    <span class="text-sm font-semibold text-green-600">156 sold</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm text-gray-900">Carrots</span>
                    <span class="text-sm font-semibold text-green-600">142 sold</span>
                </div>
            </div>
        </div>

        <!-- Top Users -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Top Buyers</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-bold">A</div>
                        <span class="text-sm text-gray-900">Alice Johnson</span>
                    </div>
                    <span class="text-sm font-semibold text-green-600">$2,450</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-bold">B</div>
                        <span class="text-sm text-gray-900">Bob Smith</span>
                    </div>
                    <span class="text-sm font-semibold text-green-600">$1,890</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center text-sm font-bold">C</div>
                        <span class="text-sm text-gray-900">Carol Davis</span>
                    </div>
                    <span class="text-sm font-semibold text-green-600">$1,670</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center text-sm font-bold">D</div>
                        <span class="text-sm text-gray-900">David Evans</span>
                    </div>
                    <span class="text-sm font-semibold text-green-600">$1,450</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Export Reports</h3>
                <p class="text-sm text-gray-600 mt-1">Download detailed reports in various formats</p>
            </div>
            <div class="flex gap-3">
                <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium">
                    <i class="fas fa-file-csv mr-2"></i>CSV
                </button>
                <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium">
                    <i class="fas fa-file-pdf mr-2"></i>PDF
                </button>
                <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    <i class="fas fa-download mr-2"></i>Excel
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
