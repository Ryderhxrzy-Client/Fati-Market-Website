@extends('layouts.admin-dashboard')

@section('title', 'Profit Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Profit Report</h3>
            <p class="text-sm text-gray-600">View total profit and markup analysis</p>
        </div>
    </div>

    <!-- Total Markup Card -->
    <div class="bg-white rounded-lg shadow p-6">
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
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Profit by Month</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Month</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Transactions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reportData['monthly_profit'] ?? [] as $month)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ $month['month'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-green-600">{{ $month['count'] ?? 0 }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No monthly profit data available</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Profitable Items -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Top Profitable Items</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Item Name</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Markup Points</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Seller</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reportData['top_items'] ?? [] as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $item['item_name'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-green-600">{{ $item['markup_points'] ?? 0 }} pts</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ $item['seller_email'] ?? 'N/A' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No profitable items data available</p>
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

