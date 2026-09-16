@extends('layouts.admin-dashboard')

@section('title', 'Private offers')
@section('subtitle', 'What students have offered the store, and where each offer stands')

@section('content')
{{--
    The offers list, as a decision queue rather than a table of rows.

    It used to show a title, a seller and an asking price, and nothing about
    what had been decided: an offer already accepted at an agreed price looked
    exactly like one nobody had opened yet. "Message seller" opened a box that
    fired a single message into the void, away from the thread it belonged to.

    Each row now says where the offer stands, and carries the three things
    that move it along: accept it at a price, receive the item - through the
    same counter panel the chat uses, phone handoff included - and open the
    conversation it belongs to.
--}}
<div class="space-y-6">
    <div class="fm-toolbar">
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search items, sellers…" id="searchInput" class="fm-input"></span>
        <select id="stageFilter" class="fm-select" style="width: auto; min-width: 190px;">
            <option value="">Every offer</option>
            <option value="review">Waiting for review</option>
            <option value="accepted">Accepted, not yet received</option>
            <option value="received">Already received</option>
        </select>
    </div>

    <div class="fm-card">
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Seller</th>
                        <th>Asking price</th>
                        <th>Offer</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $imageUrl = !empty($item['photos']) && is_array($item['photos']) ? $item['photos'][0] : null;
                            $sellerEmail = $item['seller_email'] ?? 'N/A';
                            $sellerId = $item['seller_id'] ?? null;
                            $price = $item['seller_asking_price'] ?? null;
                            $agreed = $item['acquisition_price'] ?? null;
                            $itemId = $item['item_id'] ?? $item['id'] ?? null;
                            $status = strtolower($item['status'] ?? 'pending');

                            // Where this offer stands. Accepting an offer IS
                            // agreeing a price, so a priced pending offer is
                            // an accepted one waiting for the item to arrive.
                            $received = !empty($item['is_turnover_verified'])
                                || in_array($status, ['acquired', 'public', 'reserved', 'sold'], true);
                            $accepted = $agreed !== null;

                            [$stage, $badge, $badgeTone, $badgeNote] = match (true) {
                                $status === 'rejected' => ['closed', 'Declined', 'danger', $item['rejected_reason'] ?? null],
                                $status === 'sold' => ['received', 'Sold', '', null],
                                $status === 'reserved' => ['received', 'Reserved', 'info', null],
                                $status === 'public' => ['received', 'Published', 'success', \App\Support\Peso::format($item['public_price'] ?? null, '')],
                                $received => ['received', 'Received', 'success', 'In the store'],
                                $accepted => ['accepted', 'Offer accepted', 'brand', 'Agreed at ' . \App\Support\Peso::format($agreed)],
                                default => ['review', 'Waiting for review', 'warning', 'No price agreed yet'],
                            };
                        @endphp
                        @php
                            // Deleting is for listings the store has not taken
                            // in. Once it holds the item the row is inventory
                            // and history, and the API refuses anyway.
                            $deletable = $itemId && !$received && in_array($status, ['pending', 'private', 'rejected'], true);
                        @endphp
                        <tr data-stage="{{ $stage }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    @if(!empty($imageUrl))
                                        <img src="{{ $imageUrl }}" alt="{{ $item['title'] ?? 'Item' }}" class="thumb" loading="lazy">
                                    @else
                                        <div class="thumb"><i class="fas fa-image text-gray-400"></i></div>
                                    @endif
                                    <div>
                                        <p class="cell-title">{{ $item['title'] ?? 'N/A' }}</p>
                                        <p class="cell-sub">ID: {{ $itemId ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p>{{ $sellerEmail }}</p>
                            </td>
                            <td>
                                <p class="cell-title money">{{ \App\Support\Peso::format($price) }}</p>
                                <p class="cell-sub">seller asking</p>
                            </td>
                            <td>
                                <span class="fm-badge {{ $badgeTone }}">{{ $badge }}</span>
                                @if(!empty($badgeNote))
                                    <p class="cell-sub" style="margin-top: 4px;">{{ $badgeNote }}</p>
                                @endif
                            </td>
                            <td>
                                <p>{{ isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'N/A' }}</p>
                            </td>
                            <td>
                                <div class="flex gap-2 items-center">
                                    @if($itemId && !$received && $status !== 'rejected')
                                        @if(!$accepted)
                                            <button class="fm-btn primary sm"
                                                    onclick="approveOffer({{ $itemId }}, '{{ $price }}', @js($item['title'] ?? 'this item'))">
                                                <i class="fas fa-check"></i>Approve
                                            </button>
                                        @else
                                            <button class="fm-btn primary sm" onclick="openTurnover({{ $itemId }}, () => location.reload())">
                                                <i class="fas fa-box-open"></i>Mark acquired
                                            </button>
                                        @endif
                                    @endif

                                    @if($itemId && $sellerId)
                                        <a class="row-btn" title="Open conversation"
                                           href="{{ route('admin.conversations') }}?thread={{ $itemId }}-{{ $sellerId }}">
                                            <i class="fas fa-comments"></i>
                                        </a>
                                    @endif

                                    <button class="row-btn" title="Acquisition &amp; publishing" onclick="openItemWorkflow({{ $itemId }})">
                                        <i class="fas fa-diagram-project"></i>
                                    </button>
                                    <button class="row-btn view-item-btn" title="View details" data-item-id="{{ $itemId }}" data-item-data="{{ base64_encode(json_encode($item)) }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="row-btn edit-item-btn" title="Edit item" data-item-id="{{ $itemId }}" data-item-data="{{ base64_encode(json_encode($item)) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    @if($deletable)
                                        <button class="row-btn danger" title="Delete offer"
                                                onclick="askDeleteOffer({{ $itemId }}, @js($item['title'] ?? 'this item'), @js($sellerEmail))">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No private offers found</p>
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


<!-- Accept the offer: agreeing a price is what acceptance means. -->
<div id="approveModal" class="modal-overlay">
    <div class="modal">
        <div class="flex items-center justify-between mb-4">
            <h3>Accept this offer</h3>
            <button onclick="closeApproveModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="space-y-4">
            <p class="cell-sub" id="approveSubtitle" style="margin: 0;"></p>

            <div>
                <label class="fm-label" for="approvePrice">Acquisition price (₱)</label>
                <input id="approvePrice" type="number" step="0.01" min="0" class="fm-input" placeholder="e.g. 180.00">
                <p class="cell-sub" style="margin-top: 5px;">
                    What the store pays the seller. They are told in their chat, and their turnover QR starts working.
                </p>
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeApproveModal()" class="fm-btn ghost">Cancel</button>
                <button type="button" onclick="submitApproval()" class="fm-btn primary" id="approveButton">
                    <i class="fas fa-check"></i>Accept offer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Deleting an offer: said plainly, and never on one stray click. -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal">
        <div class="flex items-center justify-between mb-4">
            <h3>Delete this offer?</h3>
            <button onclick="closeDeleteModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="space-y-4">
            <p id="deleteSubtitle" style="margin: 0; font-size: 14px;"></p>

            <ul class="cell-sub" style="margin: 0; padding-left: 18px; line-height: 1.7;">
                <li>Its photos go with it.</li>
                <li>The conversation about this item goes too, for both of you.</li>
                <li>This cannot be undone.</li>
            </ul>

            <p class="cell-sub" style="margin: 0;">
                An item the store has already received cannot be deleted here - unpublish it or reject the offer instead.
            </p>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeDeleteModal()" class="fm-btn ghost">Keep it</button>
                <button type="button" onclick="confirmDeleteOffer()" class="fm-btn danger" id="deleteButton">
                    <i class="fas fa-trash"></i>Delete offer
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .modal-lg { max-width: 900px; width: 90%; }
</style>
@endpush

@include('admin.partials.item-workflow')
@include('admin.partials.turnover')

@push('scripts')
<script>
const API = 'https://fati-api.alertaraqc.com/api';
const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') ||
    sessionStorage.getItem('admin_token') ||
    localStorage.getItem('admin_token') ||
    '';

// ── Filtering ────────────────────────────────────────────────────────────

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const stage = document.getElementById('stageFilter').value;

    document.querySelectorAll('tbody tr[data-stage]').forEach(row => {
        const matchesSearch = row.textContent.toLowerCase().includes(search);
        const matchesStage = !stage || row.dataset.stage === stage;
        row.style.display = matchesSearch && matchesStage ? '' : 'none';
    });
}

document.getElementById('searchInput').addEventListener('keyup', applyFilters);
document.getElementById('stageFilter').addEventListener('change', applyFilters);

// ── Accepting an offer ───────────────────────────────────────────────────

let approvingItemId = null;

/**
 * Accepting is recording the price the store agreed to pay. There is no
 * separate "approved" flag: a priced offer is an accepted one, which is why
 * the row reads "Offer accepted" the moment this saves.
 */
function approveOffer(itemId, askingPrice, title) {
    approvingItemId = itemId;
    document.getElementById('approveSubtitle').textContent =
        `${title} · the seller is asking ${fmPeso(askingPrice)}.`;
    document.getElementById('approvePrice').value = askingPrice || '';
    document.getElementById('approveModal').classList.add('active');
    setTimeout(() => document.getElementById('approvePrice').focus(), 60);
}

function closeApproveModal() {
    document.getElementById('approveModal').classList.remove('active');
    approvingItemId = null;
}

async function submitApproval() {
    if (!approvingItemId) return;

    const field = document.getElementById('approvePrice');
    const price = (field.value || '').trim();

    if (!price || isNaN(Number(price)) || Number(price) < 0) {
        showToast('Enter a valid peso amount, e.g. 180 or 179.50.', 'error');
        field.focus();
        return;
    }

    const button = document.getElementById('approveButton');
    button.disabled = true;
    button.textContent = 'Accepting…';

    try {
        const response = await fetch(`${API}/admin/items/${approvingItemId}/acquisition-price`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ acquisition_price: price }),
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        showToast('Offer accepted. The seller has been told in their chat.', 'success');
        closeApproveModal();
        setTimeout(() => location.reload(), 900);
    } catch (error) {
        showToast(`Could not accept the offer: ${error.message}`, 'error');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check"></i>Accept offer';
    }
}

// ── Deleting an offer ────────────────────────────────────────────────────

let deletingItemId = null;

/**
 * Only the store deletes a listing, and only one nobody has taken in. The
 * server enforces both; this asks first, because the chat about the item goes
 * with it and nothing here can be undone.
 */
function askDeleteOffer(itemId, title, sellerEmail) {
    deletingItemId = itemId;
    document.getElementById('deleteSubtitle').textContent =
        `"${title}" from ${sellerEmail} will be removed from the store for good.`;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deletingItemId = null;
}

async function confirmDeleteOffer() {
    if (!deletingItemId) return;

    const button = document.getElementById('deleteButton');
    button.disabled = true;
    button.textContent = 'Deleting…';

    try {
        const response = await fetch(`${API}/admin/items/${deletingItemId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        showToast(payload.message || 'Offer deleted', 'success');
        closeDeleteModal();
        setTimeout(() => location.reload(), 900);
    } catch (error) {
        showToast(`Could not delete: ${error.message}`, 'error');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-trash"></i>Delete offer';
    }
}

// ── The row's own buttons ────────────────────────────────────────────────

document.querySelectorAll('.view-item-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        openItemView(JSON.parse(atob(this.getAttribute('data-item-data'))));
    });
});

document.querySelectorAll('.edit-item-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        openItemEdit(JSON.parse(atob(this.getAttribute('data-item-data'))));
    });
});

document.getElementById('approveModal').addEventListener('click', function (e) {
    if (e.target === this) closeApproveModal();
});

document.getElementById('deleteModal').addEventListener('click', function (e) {
    if (e.target === this) closeDeleteModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    closeApproveModal();
    closeDeleteModal();
});
</script>
@endpush

@include('admin.partials.item-view')
@include('admin.partials.item-edit')
@endsection
