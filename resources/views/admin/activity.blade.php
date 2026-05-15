@extends('layouts.admin-dashboard')

@section('title', 'Activity Logs')

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                <input type="text" placeholder="Search user..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">All Actions</option>
                    <option value="login">Login</option>
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="flex items-end">
                <button class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="divide-y divide-gray-200">
            @forelse($activities ?? [] as $activity)
                <div class="p-6 hover:bg-gray-50 transition flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                            @php
                                $action = strtolower($activity['action'] ?? 'unknown');
                                $icon = match($action) {
                                    'login' => 'fa-sign-in-alt',
                                    'logout' => 'fa-sign-out-alt',
                                    'create' => 'fa-plus',
                                    'update' => 'fa-edit',
                                    'delete' => 'fa-trash',
                                    'purchase' => 'fa-shopping-cart',
                                    default => 'fa-circle',
                                };
                            @endphp
                            <i class="fas {{ $icon }} text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-sm font-semibold text-gray-900">{{ $activity['user'] ?? 'Unknown User' }}</h4>
                            <span class="text-xs text-gray-500">{{ ucfirst($action) }}</span>
                        </div>
                        <p class="text-sm text-gray-600">{{ $activity['description'] ?? 'No description available' }}</p>
                        <p class="text-xs text-gray-500 mt-2">{{ $activity['timestamp'] ?? 'N/A' }}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="text-xs font-medium px-3 py-1 rounded-full bg-gray-100 text-gray-700">
                            {{ $activity['resource_type'] ?? 'N/A' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 mt-2">No activity logs found</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
