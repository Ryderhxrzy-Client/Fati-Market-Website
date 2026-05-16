@extends('layouts.admin-dashboard')

@section('title', 'Profit Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Profit Report</h3>
            <p class="text-sm text-gray-600">View total profit from markups</p>
        </div>
        <div class="flex gap-3">
            <input type="search" placeholder="Search reports..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Profit Summary -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="text-sm text-gray-600 font-medium">Total Profit</p>
                <p class="text-4xl font-bold text-green-600 mt-2">{{ $reportData['total_profit'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-green-600"></i>
            </div>
        </div>
    </div>

    <!-- Profit Breakdown -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Period</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Amount</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reportData['breakdown'] ?? [] as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ $item['period'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-green-600">{{ $item['amount'] ?? 0 }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    Active
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No profit data available</p>
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
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
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
