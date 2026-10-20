@extends('layouts.admin-dashboard')

@section('title', 'Cash transactions')
@section('subtitle', 'View all cash transactions')

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
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        @php
                            $itemTitle = $transaction['item']['title'] ?? $transaction['item_title'] ?? ('Item #' . ($transaction['item_id'] ?? 'N/A'));
                            $itemPhotos = $transaction['item']['photos'] ?? [];
                            $photo = !empty($itemPhotos) ? $itemPhotos[0] : null;
                            $buyer = $transaction['buyer'] ?? [];
                            $buyerName = is_array($buyer) ? ($buyer['name'] ?? null) : null;
                            $buyerEmail = is_array($buyer) ? ($buyer['email'] ?? null) : ($transaction['buyer_email'] ?? null);
                            $seller = $transaction['seller'] ?? [];
                            $sellerEmail = is_array($seller) ? ($seller['email'] ?? null) : ($transaction['seller_email'] ?? null);
                            $consignedBy = $transaction['consigned_by'] ?? null;
                            $amount = $transaction['amount_due'] ?? $transaction['amount'] ?? 0;
                            $receiptNo = $transaction['receipt_no'] ?? ('#' . ($transaction['transaction_id'] ?? ''));
                            $date = $transaction['created_at'] ?? $transaction['transaction_date'] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <p class="cell-title font-medium text-gray-900">{{ $receiptNo }}</p>
                                <p class="cell-sub text-xs text-gray-500">#{{ $transaction['transaction_id'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if($photo)
                                        <img src="{{ $photo }}" alt="" class="thumb" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;" loading="lazy">
                                    @else
                                        <div class="thumb" style="width: 40px; height: 40px; border-radius: 6px; background: var(--surface-sunk, #f3f4f6); display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-gray-400"></i></div>
                                    @endif
                                    <p class="cell-title text-sm font-medium text-gray-900" style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $itemTitle }}</p>
                                </div>
                            </td>
                            <td>
                                <div class="space-y-1">
                                    @if($buyerName || $buyerEmail)
                                        <p class="text-xs text-gray-900 font-medium">Buyer: {{ $buyerName ?? $buyerEmail }}</p>
                                        @if($buyerName && $buyerEmail)
                                            <p class="text-xs text-gray-500">{{ $buyerEmail }}</p>
                                        @endif
                                    @endif
                                    @if($sellerEmail)
                                        <p class="text-xs text-gray-600">Seller: {{ $sellerEmail }}</p>
                                    @endif
                                    @if($consignedBy)
                                        <p class="text-xs text-gray-500">From: {{ $consignedBy }}</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-gray-900">₱{{ number_format((float)$amount, 2) }}</p>
                            </td>
                            <td>
                                <p class="text-xs font-medium text-gray-700 capitalize">{{ str_replace('_', ' ', $transaction['payment_method'] ?? 'cash') }}</p>
                                @if(isset($transaction['payment_status']))
                                    <span class="text-xs text-gray-500">({{ ucfirst($transaction['payment_status']) }})</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $status = $transaction['status'] ?? 'pending';
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-800',
                                        'reserved' => 'bg-blue-100 text-blue-800',
                                        'ready_for_pickup' => 'bg-emerald-100 text-emerald-800',
                                        'pending' => 'bg-orange-100 text-orange-800',
                                        'pending_payment' => 'bg-orange-100 text-orange-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </span>
                            </td>
                            <td>
                                <p class="text-xs text-gray-600">{{ $date ? date('M d, Y H:i', strtotime($date)) : 'N/A' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No cash transactions found</p>
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
