@extends('layouts.admin-dashboard')

@section('title', 'Items Acquired')
@section('subtitle', 'Every item the store has taken in and what it agreed to pay')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="fm-card fm-card-body">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Total items acquired</p>
                    <p class="stat-value text-4xl mt-2">{{ $reportData['total_acquired'] ?? 0 }}</p>
                </div>
                <div class="stat-icon" style="background: var(--info-bg);">
                    <i class="fas fa-warehouse" style="color: var(--info);"></i>
                </div>
            </div>
        </div>

        <div class="fm-card fm-card-body">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Total items sold</p>
                    <p class="stat-value text-4xl mt-2">{{ $reportData['total_sold'] ?? 0 }}</p>
                </div>
                <div class="stat-icon" style="background: var(--success-bg);">
                    <i class="fas fa-boxes-stacked" style="color: var(--success);"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="fm-card">
        <div class="fm-card-head">
            <div>
                <h4>Acquired inventory</h4>
                <p class="cell-sub">The items behind the number above - each one physically in the store.</p>
            </div>
        </div>

        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Seller</th>
                        <th>Acquisition price</th>
                        <th>Acquired</th>
                        <th>Seller payout</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($reportData['items'] ?? [] as $item)
                        <tr>
                            <td><p class="cell-title">{{ $item['title'] }}</p></td>
                            <td><p>{{ $item['seller_email'] }}</p></td>
                            <td><p class="cell-title money">{{ \App\Support\Peso::format($item['acquisition_price']) }}</p></td>
                            <td>
                                <p>{{ !empty($item['acquired_at']) ? date('M d, Y', strtotime($item['acquired_at'])) : '—' }}</p>
                            </td>
                            <td>
                                @if(($item['seller_payout_status'] ?? 'unpaid') === 'paid')
                                    <span class="fm-badge success">Paid</span>
                                @else
                                    <span class="fm-badge warning">Unpaid</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="fm-empty">
                                    <i class="fas fa-warehouse"></i>
                                    <p>Nothing acquired yet</p>
                                    <span>Items appear here once their turnover is verified.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
