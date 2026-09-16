@extends('layouts.admin-dashboard')

@section('title', 'Sold items')
@section('subtitle', 'Items that have been successfully sold')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="fm-toolbar">
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search items..." id="searchInput" class="fm-input"></span>
    </div>

    <!-- Items Grid/List -->
    <div class="fm-card">
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Buyer</th>
                        <th>Sold for</th>
                        <th>Payment</th>
                        <th>Points earned</th>
                        <th>Sold on</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $imageUrl = !empty($item['photos']) && is_array($item['photos']) ? $item['photos'][0] : null;
                            $sellerEmail = $item['seller_email'] ?? 'N/A';
                            $price = $item['public_price'] ?? null;
                            $itemId = $item['item_id'] ?? $item['id'] ?? 'N/A';

                            // The order that ended the listing. What was paid,
                            // how, and what the buyer earned back all live
                            // here rather than on the item.
                            $sale = $item['sale'] ?? null;
                            $paidAmount = $sale['amount_due'] ?? null;
                            $pointsEarned = (int) ($sale['reward_points_earned'] ?? 0);
                            $pointsUsed = (int) ($sale['points_used'] ?? 0);
                            $soldOn = $sale['completed_at'] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if(!empty($imageUrl))
                                        <img src="{{ $imageUrl }}" alt="{{ $item['title'] ?? 'Item' }}" class="thumb" loading="lazy">
                                    @else
                                        <div class="thumb">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="cell-title">{{ $item['title'] ?? 'N/A' }}</p>
                                        <p class="cell-sub">ID: {{ $itemId }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($sale)
                                    <p class="cell-title">{{ $sale['buyer_name'] ?? $sale['buyer_email'] ?? 'Buyer' }}</p>
                                    <p class="cell-sub">{{ $sale['receipt_no'] ?? '' }}</p>
                                @else
                                    <p class="cell-sub">No order on record</p>
                                @endif
                                <p class="cell-sub">from {{ $sellerEmail }}</p>
                            </td>
                            <td>
                                <p class="cell-title money">{{ \App\Support\Peso::format($paidAmount ?? $price) }}</p>
                                <p class="cell-sub">
                                    @if($pointsUsed > 0)
                                        {{ $pointsUsed }} point(s) used
                                    @else
                                        markup {{ \App\Support\Peso::format($item['markup'] ?? null) }}
                                    @endif
                                </p>
                            </td>
                            <td>
                                @if($sale)
                                    <span class="fm-badge {{ ($sale['payment_method'] ?? '') === 'cash' ? '' : 'info' }}">
                                        {{ ucfirst($sale['payment_method'] ?? 'unknown') }}
                                    </span>
                                    <p class="cell-sub" style="margin-top: 4px;">{{ ucfirst(str_replace('_', ' ', $sale['payment_status'] ?? '')) }}</p>
                                @else
                                    <p class="cell-sub">—</p>
                                @endif
                            </td>
                            <td>
                                @if($pointsEarned > 0)
                                    <span class="fm-badge reward">+{{ $pointsEarned }} point(s)</span>
                                @else
                                    <p class="cell-sub">None</p>
                                @endif
                            </td>
                            <td>
                                <p>{{ $soldOn ? date('M d, Y', strtotime($soldOn)) : (isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'N/A') }}</p>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <button class="row-btn view-item-btn" title="View details" data-item-id="{{ $itemId }}" data-item-data="{{ base64_encode(json_encode($item)) }}">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No sold items found</p>
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

<!-- View Item Modal -->

@push('styles')
<style>
    .modal-lg {
        max-width: 900px;
        width: 90%;
    }
</style>
@endpush

@push('scripts')
<script>
    const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') ||
        sessionStorage.getItem('admin_token') ||
        localStorage.getItem('admin_token') ||
        '';

    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    document.querySelectorAll('.view-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemData = JSON.parse(atob(this.getAttribute('data-item-data')));
            openItemView(itemData);
        });
    });
</script>
@endpush

@include('admin.partials.item-view')
@endsection
