@extends('layouts.admin-dashboard')

@section('title', 'Users Report')
@section('subtitle', 'View active users, students, and top buyers/sellers')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <!-- User Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Active Users -->
        <div class="fm-card fm-card-body">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Active Users</p>
                    <p class="text-4xl font-bold text-green-600 mt-2">{{ $reportData['active_users'] ?? 0 }}</p>
    </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fas fa-user-check text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Students -->
        <div class="fm-card fm-card-body">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Students</p>
                    <p class="text-4xl font-bold text-blue-600 mt-2">{{ $reportData['total_students'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Buyers -->
    <div class="fm-card">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Top Buyers</h4>
        </div>
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Wallet Points</th>
                        <th>Transactions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['top_buyers'] ?? [] as $buyer)
                        <tr>
                            <td>
                                <p class="text-sm font-medium text-gray-900">{{ $buyer['email'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-blue-600">{{ $buyer['wallet_points'] ?? 0 }} pts</p>
                            </td>
                            <td>
                                <p>{{ $buyer['transaction_count'] ?? 0 }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No top buyers data available</p>
                                    <span>Nothing to show here yet.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Sellers -->
    <div class="fm-card">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Top Sellers</h4>
        </div>
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Wallet Points</th>
                        <th>Transactions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['top_sellers'] ?? [] as $seller)
                        <tr>
                            <td>
                                <p class="text-sm font-medium text-gray-900">{{ $seller['email'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-green-600">{{ $seller['wallet_points'] ?? 0 }} pts</p>
                            </td>
                            <td>
                                <p>{{ $seller['transaction_count'] ?? 0 }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No top sellers data available</p>
                                    <span>Nothing to show here yet.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('searchInput')?.addEventListener('keyup', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            if (row.querySelector('td')) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        });
    });
</script>
@endpush
@endsection
