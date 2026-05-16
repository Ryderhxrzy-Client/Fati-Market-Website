@extends('layouts.admin-dashboard')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- USERS SECTION -->
    <div>
        <h3 class="text-sm font-semibold text-green-700 uppercase tracking-wide mb-4">USERS</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total</p>
                        <div class="stat-value text-2xl">{{ $stats['users']['total_students'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Active</p>
                        <div class="stat-value text-2xl">{{ $stats['users']['active_students'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Pending</p>
                        <div class="stat-value text-2xl">{{ $stats['users']['pending_students'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                        <i class="fas fa-hourglass-half text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Verified</p>
                        <div class="stat-value text-2xl">{{ $stats['users']['verified_students'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-check-double text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ITEMS SECTION -->
    <div>
        <h3 class="text-sm font-semibold text-green-700 uppercase tracking-wide mb-4">ITEMS</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total</p>
                        <div class="stat-value text-2xl">{{ $stats['items']['total_items'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-store text-indigo-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Public</p>
                        <div class="stat-value text-2xl">{{ $stats['items']['public_items'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center">
                        <i class="fas fa-globe text-cyan-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Private</p>
                        <div class="stat-value text-2xl">{{ $stats['items']['private_items'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                        <i class="fas fa-lock text-red-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Acquired</p>
                        <div class="stat-value text-2xl">{{ $stats['items']['acquired_items'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-download text-orange-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Reserved</p>
                        <div class="stat-value text-2xl">{{ $stats['items']['reserved_items'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center">
                        <i class="fas fa-bookmark text-pink-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Sold</p>
                        <div class="stat-value text-2xl">{{ $stats['items']['sold_items'] ?? 0 }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-teal-100 flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-teal-600"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT ACTIVITIES -->
    @php
        $recentActivities = $stats['recent_activities'] ?? [];
        $registrations = $recentActivities['recent_registrations'] ?? [];
        $items = $recentActivities['recent_items'] ?? [];
        $verifications = $recentActivities['pending_verifications'] ?? [];
    @endphp

    @if (!empty($registrations))
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-green-700 uppercase tracking-wide">RECENT REGISTRATIONS</h3>
            <a href="{{ route('admin.students') }}" class="text-green-600 text-sm font-medium hover:text-green-700">View All</a>
        </div>
        <div class="space-y-3">
            @foreach($registrations as $registration)
                <div class="bg-white rounded-lg shadow p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $registration['name'] ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500">{{ $registration['email'] ?? 'N/A' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @if (!empty($items))
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-green-700 uppercase tracking-wide">RECENT ITEMS</h3>
            <a href="{{ route('admin.private-offers') }}" class="text-green-600 text-sm font-medium hover:text-green-700">View All</a>
        </div>
        <div class="space-y-3">
            @foreach($items as $item)
                <div class="bg-white rounded-lg shadow p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shopping-bag text-indigo-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $item['title'] ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $item['seller'] ?? 'N/A' }} • {{ strtoupper($item['status'] ?? 'pending') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @if (!empty($verifications))
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-orange-700 uppercase tracking-wide">PENDING VERIFICATIONS</h3>
            <a href="{{ route('admin.students') }}" class="text-orange-600 text-sm font-medium hover:text-orange-700">Review</a>
        </div>
        <div class="space-y-3">
            @foreach($verifications as $verification)
                <div class="bg-white rounded-lg shadow p-4 flex items-center gap-4 border-l-4 border-orange-500">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-certificate text-orange-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $verification['student_name'] ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $verification['email'] ?? 'N/A' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

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
                <a href="{{ route('admin.private-offers') }}" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                    <span class="text-gray-700">Manage Items</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                </a>
                <a href="{{ route('admin.conversations') }}" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                    <span class="text-gray-700">View Messages</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                </a>
                <a href="{{ route('admin.reports') }}" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                    <span class="text-gray-700">View Reports</span>
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
                    <span class="text-gray-700">Cache</span>
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">Session Storage</span>
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
