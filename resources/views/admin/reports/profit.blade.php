@extends('layouts.admin-dashboard')

@section('title', 'Profit from markup')
@section('subtitle', 'What the store earned above what it paid, from every sold item')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="fm-card fm-card-body">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Total profit from markup</p>
                    <p class="stat-value text-4xl mt-2 money" style="color: var(--success);">{{ \App\Support\Peso::format($reportData['total_profit'] ?? null, '₱0.00') }}</p>
                </div>
                <div class="stat-icon" style="background: var(--success-bg);">
                    <i class="fas fa-coins" style="color: var(--success);"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="fm-card">
        <div class="fm-card-head">
            <div>
                <h4>Profit by month</h4>
                <p class="cell-sub">Markup on the items sold each month.</p>
            </div>
        </div>
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="num">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['profit_by_month'] ?? [] as $month)
                        <tr>
                            <td><p class="cell-title">{{ $month['month'] ?? 'N/A' }}</p></td>
                            <td class="num"><p class="cell-title money" style="color: var(--success);">{{ \App\Support\Peso::format($month['profit'] ?? null) }}</p></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                <div class="fm-empty">
                                    <i class="fas fa-chart-line"></i>
                                    <p>No profit yet</p>
                                    <span>Months appear here once an item is sold.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="fm-card">
        <div class="fm-card-head">
            <div>
                <h4>Top profitable items</h4>
                <p class="cell-sub">Sold items ranked by markup.</p>
            </div>
        </div>
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Seller</th>
                        <th class="num">Sold for</th>
                        <th class="num">Acquisition</th>
                        <th class="num">Markup</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['top_items'] ?? [] as $item)
                        <tr>
                            <td><p class="cell-title">{{ $item['title'] ?? 'N/A' }}</p></td>
                            <td><p>{{ $item['seller_email'] ?? 'N/A' }}</p></td>
                            <td class="num"><p class="money">{{ \App\Support\Peso::format($item['public_price'] ?? null) }}</p></td>
                            <td class="num"><p class="money">{{ \App\Support\Peso::format($item['acquisition_price'] ?? null) }}</p></td>
                            <td class="num"><p class="cell-title money" style="color: var(--success);">{{ \App\Support\Peso::format($item['markup'] ?? null) }}</p></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="fm-empty">
                                    <i class="fas fa-coins"></i>
                                    <p>Nothing sold yet</p>
                                    <span>Items appear here once an order for them is completed.</span>
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
