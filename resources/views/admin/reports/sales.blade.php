@extends('layouts.admin-dashboard')

@section('title', 'Sales Report')
@section('subtitle', 'View items acquired and sold summary')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <!-- Sales Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Total Items Acquired -->
        <div class="fm-card fm-card-body">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Items Acquired</p>
                    <p class="text-4xl font-bold text-blue-600 mt-2">{{ $reportData['summary']['total_items_acquired'] ?? 0 }}</p>
    </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Items Sold -->
        <div class="fm-card fm-card-body">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Items Sold</p>
                    <p class="text-4xl font-bold text-green-600 mt-2">{{ $reportData['summary']['total_items_sold'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fas fa-boxes text-green-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Transactions Table -->
    <div class="fm-card">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Recent Sales</h4>
        </div>
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Item Name</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Points Used</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['sales'] ?? [] as $sale)
                        <tr>
                            <td>
                                <p class="text-sm font-medium text-gray-900">#{{ $sale['transaction_id'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p>{{ $sale['item_name'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p>{{ $sale['buyer_email'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p>{{ $sale['seller_email'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-blue-600">{{ $sale['points_used'] ?? 0 }}</p>
                            </td>
                            <td>
                                @php
                                    $status = $sale['status'] ?? 'pending';
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-orange-100 text-orange-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No sales data available</p>
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
