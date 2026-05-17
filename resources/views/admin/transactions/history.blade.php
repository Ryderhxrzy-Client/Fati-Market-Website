@extends('layouts.admin-dashboard')

@section('title', 'Transaction History')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Transaction History</h3>
            <p class="text-sm text-gray-600">View all transaction records</p>
        </div>
        <div class="flex gap-3">
            <input type="search" placeholder="Search transactions..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Transaction ID</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Item</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Buyer / Seller</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Payment Method</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Points Used</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $transaction['transaction_id'] ?? 'TXN-0000' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $transaction['item_title'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    @if($transaction['buyer_email'] ?? null)
                                        <p class="text-xs text-gray-600">Buyer: {{ $transaction['buyer_email'] }}</p>
                                    @endif
                                    @if($transaction['seller_email'] ?? null)
                                        <p class="text-xs text-gray-600">Seller: {{ $transaction['seller_email'] }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ $transaction['payment_method'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-blue-600">{{ $transaction['points_used'] ?? '0' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $status = $transaction['status'] ?? 'pending';
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-orange-100 text-orange-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'success' => 'bg-green-100 text-green-800',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No transactions found</p>
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
