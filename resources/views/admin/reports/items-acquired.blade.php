@extends('layouts.admin-dashboard')

@section('title', 'Total Item Acquired')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Total Item Acquired</h3>
            <p class="text-sm text-gray-600">View acquired totals and related transactions</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Items Acquired</p>
                    <p class="text-4xl font-bold text-blue-600 mt-2">{{ $reportData['total_acquired'] ?? ($reportData['summary']['total_items_acquired'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
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

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Transactions (Acquired)</h4>
            <p class="text-sm text-gray-600">Item name, buyer, seller, points used, and status</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Item Name</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Buyer</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Seller</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Points Used</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse($reportData['sales'] ?? [] as $sale)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $sale['item_name'] ?? $sale['item_name'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ $sale['buyer_email'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ $sale['seller_email'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-blue-600">{{ $sale['points_used'] ?? 0 }}</p>
                            </td>
                            <td class="px-6 py-4">
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
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No acquired transactions data available</p>
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

