@extends('layouts.admin-dashboard')

@section('title', 'Transaction History')
@section('subtitle', 'View all transaction records')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="fm-toolbar">
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search transactions..." id="searchInput" class="fm-input"></span>
    </div>

    <!-- Transactions Table -->
    <div class="fm-card">
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Item</th>
                        <th>Buyer / Seller</th>
                        <th>Payment Method</th>
                        <th>Points Used</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <p class="text-sm font-medium text-gray-900">{{ $transaction['transaction_id'] ?? 'TXN-0000' }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-medium text-gray-900">{{ $transaction['item_title'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <div class="space-y-1">
                                    @if($transaction['buyer_email'] ?? null)
                                        <p class="text-xs text-gray-600">Buyer: {{ $transaction['buyer_email'] }}</p>
                                    @endif
                                    @if($transaction['seller_email'] ?? null)
                                        <p class="text-xs text-gray-600">Seller: {{ $transaction['seller_email'] }}</p>
                                    @endif
                                    @if($transaction['consigned_by'] ?? null)
                                        <p class="text-xs text-gray-500">From: {{ $transaction['consigned_by'] }}</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <p>{{ $transaction['payment_method'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-blue-600">{{ $transaction['points_used'] ?? '0' }}</p>
                            </td>
                            <td>
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
                            <td colspan="6">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No transactions found</p>
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
