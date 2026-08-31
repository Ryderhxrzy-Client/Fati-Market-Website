@extends('layouts.admin-dashboard')

@section('title', 'Profit Report')
@section('subtitle', 'View total profit and markup analysis')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <!-- Total Markup Card -->
    <div class="fm-card fm-card-body">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-gray-600 font-medium">Total Markup Profit</p>
                <p class="text-4xl font-bold text-green-600 mt-2">{{ $reportData['total_markup'] ?? 0 }} pts</p>
    </div>
            <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-green-600"></i>
            </div>
        </div>
    </div>

    <!-- Profit by Month -->
    <div class="fm-card">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Profit by Month</h4>
        </div>
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Transactions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['monthly_profit'] ?? [] as $month)
                        <tr>
                            <td>
                                <p>{{ $month['month'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-green-600">{{ $month['count'] ?? 0 }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No monthly profit data available</p>
                                    <span>Nothing to show here yet.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Profitable Items -->
    <div class="fm-card">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Top Profitable Items</h4>
        </div>
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Markup Points</th>
                        <th>Seller</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['top_items'] ?? [] as $item)
                        <tr>
                            <td>
                                <p class="text-sm font-medium text-gray-900">{{ $item['item_name'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-green-600">{{ $item['markup_points'] ?? 0 }} pts</p>
                            </td>
                            <td>
                                <p>{{ $item['seller_email'] ?? 'N/A' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No profitable items data available</p>
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

