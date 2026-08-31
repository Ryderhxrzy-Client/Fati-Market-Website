@extends('layouts.admin-dashboard')

@section('title', 'Items Sold')
@section('subtitle', 'Everything that has left the store, with its price and markup')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                <h4>Sold items</h4>
                <p class="cell-sub">The items behind the number above, with what each earned.</p>
            </div>
        </div>

        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Seller</th>
                        <th>Sold for</th>
                        <th>Acquisition</th>
                        <th>Markup</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($reportData['items'] ?? [] as $item)
                        <tr>
                            <td><p class="cell-title">{{ $item['title'] }}</p></td>
                            <td><p>{{ $item['seller_email'] }}</p></td>
                            <td><p class="cell-title money">{{ \App\Support\Peso::format($item['public_price']) }}</p></td>
                            <td><p class="money">{{ \App\Support\Peso::format($item['acquisition_price']) }}</p></td>
                            <td><p class="cell-title money" style="color: var(--success);">{{ \App\Support\Peso::format($item['markup']) }}</p></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="fm-empty">
                                    <i class="fas fa-boxes-stacked"></i>
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
