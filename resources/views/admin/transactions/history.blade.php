@extends('layouts.admin-dashboard')

@php($manage = ($mode ?? 'history') === 'manage')
@section('title', $manage ? 'Manage orders' : 'Transaction history')
@section('subtitle', $manage ? 'Approve, decline, stage and complete every order' : 'Every order on record - pending, reserved, unpaid, completed')

@section('actions')
    {{-- COUNTER SCAN DISABLED
    <a href="{{ route('admin.counter') }}" class="fm-btn ghost">
        <i class="fas fa-qrcode"></i>Counter
    </a>
    --}}
@endsection

@section('content')
<div class="space-y-6">
    {{--
        Not a read-only ledger any more. The mobile admin app lets Ofelia act
        on an order from its list - approve the payment, decline it, mark it
        ready, complete the handover, cancel - and so does this page, from
        the same endpoints and the same server-supplied `available_actions`.
    --}}
    <div class="fm-toolbar" style="justify-content: space-between;">
        <div class="flex items-center gap-2 flex-wrap" id="statusChips"></div>
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search orders…" id="searchInput" class="fm-input"></span>
    </div>

    <div class="fm-card">
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Item</th>
                        <th>Buyer</th>
                        <th class="num">Amount due</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Placed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="ordersBody">
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <span class="loading-spinner"></span>
                            <p class="cell-sub" style="margin-top: 8px;">Loading orders…</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('admin.partials.order-actions')
@include('admin.partials.pickup')

@push('styles')
<style>
    .status-chip {
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid var(--line-strong);
        background: var(--surface);
        color: var(--ink-700);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .status-chip.on { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
    .status-chip .n { font-size: 11px; opacity: 0.75; }
</style>
@endpush

@push('scripts')
<script>
    const API = 'https://fati-api.alertaraqc.com/api';
    const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    // "Manage orders" offers the decisions; "Transaction history" only shows them.
    const MANAGE = @json($manage);

    const FILTERS = [
        ['', 'All'],
        ['pending_payment', 'Awaiting'],
        ['payment_proof_submitted', 'Proof submitted'],
        ['reserved', 'Reserved'],
        ['ready_for_pickup', 'Ready for pickup'],
        ['completed', 'Completed'],
        ['cancelled', 'Cancelled'],
        ['rejected', 'Rejected'],
    ];

    let orders = [];
    let filter = '';
    let query = '';

    async function loadOrders() {
        try {
            const response = await fetch(`${API}/admin/transactions`, {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            orders = payload.data || [];
            renderChips();
            renderRows();
        } catch (error) {
            document.getElementById('ordersBody').innerHTML = `
                <tr><td colspan="8">
                    <div class="fm-empty">
                        <i class="fas fa-cloud-slash"></i>
                        <p>Could not load orders</p>
                        <span>${FMOrders.esc(error.message)}</span>
                        <div class="mt-4"><button class="fm-btn primary" onclick="loadOrders()">Try again</button></div>
                    </div>
                </td></tr>`;
        }
    }

    function renderChips() {
        const counts = {};
        orders.forEach(o => { counts[o.status] = (counts[o.status] || 0) + 1; });

        document.getElementById('statusChips').innerHTML = FILTERS.map(([value, label]) => {
            const n = value ? (counts[value] || 0) : orders.length;
            return `<button class="status-chip ${filter === value ? 'on' : ''}" onclick="setFilter('${value}')">${label} <span class="n">${n}</span></button>`;
        }).join('');
    }

    function setFilter(value) {
        filter = value;
        renderChips();
        renderRows();
    }

    function matches(order) {
        if (filter && order.status !== filter) return false;
        if (!query) return true;
        const hay = [
            order.receipt_no, order.transaction_id, order.item?.title, order.buyer?.email, order.buyer?.name,
            order.payment_method, order.status, order.payment_reference,
        ].map(v => String(v ?? '').toLowerCase()).join(' ');
        return hay.includes(query);
    }

    function renderRows() {
        const body = document.getElementById('ordersBody');
        const visible = orders.filter(matches);

        if (!visible.length) {
            body.innerHTML = `
                <tr><td colspan="8">
                    <div class="fm-empty">
                        <i class="fas fa-receipt"></i>
                        <p>No orders here</p>
                        <span>${orders.length ? 'Nothing matches this filter.' : 'Orders appear as buyers check out.'}</span>
                    </div>
                </td></tr>`;
            return;
        }

        body.innerHTML = visible.map(order => {
            const item = order.item || {};
            const buyer = order.buyer || {};
            const photo = (item.photos && item.photos[0]) || null;
            const open = MANAGE && (order.available_actions || []).length > 0;

            return `
                <tr style="cursor: pointer;" onclick="openOrder(${Number(order.transaction_id)})">
                    <td>
                        <p class="cell-title">${FMOrders.esc(order.receipt_no || ('#' + order.transaction_id))}</p>
                        <p class="cell-sub">#${FMOrders.esc(order.transaction_id)}</p>
                    </td>
                    <td>
                        <div class="flex items-center gap-3">
                            ${photo ? `<img src="${FMOrders.attr(photo)}" alt="" class="thumb" loading="lazy">` : '<div class="thumb"><i class="fas fa-image"></i></div>'}
                            <p class="cell-title" style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${FMOrders.esc(item.title || ('Item #' + order.item_id))}</p>
                        </div>
                    </td>
                    <td>
                        <p class="cell-title">${FMOrders.esc(buyer.name || (buyer.email || '').split('@')[0] || 'Buyer')}</p>
                        <p class="cell-sub">${FMOrders.esc(buyer.email || '')}</p>
                    </td>
                    <td class="num"><span class="cell-title money">${FMOrders.peso(order.amount_due)}</span></td>
                    <td>
                        <p style="margin-bottom: 4px;">${FMOrders.esc(FMOrders.methodLabel(order.payment_method))}</p>
                        ${FMOrders.paymentBadge(order)}
                    </td>
                    <td>${FMOrders.statusBadge(order)}</td>
                    <td><p class="cell-sub">${FMOrders.esc(FMOrders.when(order.created_at || order.transaction_date))}</p></td>
                    <td>
                        <button class="fm-btn ${open ? 'primary' : 'ghost'} sm" onclick="event.stopPropagation(); openOrder(${Number(order.transaction_id)})">
                            ${open ? '<i class="fas fa-bolt"></i>Decide' : '<i class="fas fa-eye"></i>View'}
                        </button>
                    </td>
                </tr>`;
        }).join('');
    }

    function openOrder(transactionId) {
        const order = orders.find(o => Number(o.transaction_id) === Number(transactionId));
        if (!order) return;

        FMOrders.open(order, (updated) => {
            const index = orders.findIndex(o => Number(o.transaction_id) === Number(updated.transaction_id));
            if (index !== -1) orders[index] = updated;
            renderChips();
            renderRows();
        }, { readOnly: !MANAGE });
    }

    document.getElementById('searchInput').addEventListener('input', function (e) {
        query = e.target.value.trim().toLowerCase();
        renderRows();
    });

    loadOrders();
</script>
@endpush
@endsection
