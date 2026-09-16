@extends('layouts.admin-dashboard')

@section('title', 'Acquired items')
@section('subtitle', 'In the store, waiting to go on sale')

@section('content')
{{--
    The shelf between the counter and the catalog.

    What this page is for is putting an item on sale, and that was the one
    thing it could not do: it offered "Send points", left over from when
    sellers were paid in wallet points rather than cash, and a message box that
    fired a line into the void away from the seller's thread. Publishing meant
    finding the workflow panel and knowing which step to open.

    Now the row carries it: what the store paid, what it will sell for, and a
    Publish button that asks for the price and shows what the buyer earns
    before it goes live. Everything else about the item - name, description,
    category, price, photos - is edited in the same editor the other inventory
    pages use.
--}}
<div class="space-y-6">
    <div class="fm-toolbar">
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search items, sellers…" id="searchInput" class="fm-input"></span>
        <select id="stageFilter" class="fm-select" style="width: auto; min-width: 200px;">
            <option value="">Everything in the store</option>
            <option value="unpriced">No selling price yet</option>
            <option value="priced">Priced, not published</option>
            <option value="unpaid">Seller not paid yet</option>
        </select>
    </div>

    <div class="fm-card">
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Seller</th>
                        <th>Acquisition</th>
                        <th>Selling price</th>
                        <th>Received</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $imageUrl = !empty($item['photos']) && is_array($item['photos']) ? $item['photos'][0] : null;
                            $sellerEmail = $item['seller_email'] ?? 'N/A';
                            $sellerId = $item['seller_id'] ?? null;
                            $acquisition = $item['acquisition_price'] ?? $item['seller_asking_price'] ?? null;
                            $publicPrice = $item['public_price'] ?? null;
                            $itemId = $item['item_id'] ?? $item['id'] ?? null;
                            $status = strtolower($item['status'] ?? 'acquired');
                            $paid = ($item['seller_payout_status'] ?? 'unpaid') === 'paid';
                            $published = $status === 'public';

                            // What the row is waiting for, which is what the
                            // filter above sorts on.
                            $stage = match (true) {
                                !$paid => 'unpaid',
                                $publicPrice === null => 'unpriced',
                                default => 'priced',
                            };

                            $received = $item['acquired_at'] ?? null;

                            // What the counter photographed when the item came
                            // in: the item itself, and the seller with their
                            // cash. Proof that the payout on this row happened.
                            $turnoverPhoto = $item['turnover_photo'] ?? null;
                            $payoutPhoto = $item['seller_payout_photo'] ?? null;
                            $hasProof = $turnoverPhoto || $payoutPhoto;
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
                                <span class="fm-badge {{ $paid ? 'success' : 'warning' }}">{{ $paid ? 'Seller paid' : 'Seller unpaid' }}</span>
                                @if($hasProof)
                                    <button type="button" class="fm-btn ghost sm proof-btn" style="margin-top: 6px;"
                                            data-title="{{ $item['title'] ?? 'this item' }}"
                                            data-item-photo="{{ $turnoverPhoto }}"
                                            data-payout-photo="{{ $payoutPhoto }}"
                                            data-amount="{{ \App\Support\Peso::format($item['seller_payout_amount'] ?? $acquisition) }}">
                                        <i class="fas fa-camera"></i>Payment proof
                                    </button>
                                @endif
                            </td>
                            <td>
                                <p class="cell-title money">{{ \App\Support\Peso::format($acquisition) }}</p>
                                <p class="cell-sub">what the store paid</p>
                            </td>
                            <td>
                                @if($publicPrice !== null)
                                    <p class="cell-title money">{{ \App\Support\Peso::format($publicPrice) }}</p>
                                    <p class="cell-sub">
                                        @if($acquisition !== null)
                                            markup {{ \App\Support\Peso::format((string) ((float) $publicPrice - (float) $acquisition)) }}
                                        @else
                                            not published yet
                                        @endif
                                    </p>
                                @else
                                    <p class="cell-sub">Not priced yet</p>
                                @endif
                            </td>
                            <td>
                                <p>{{ $received ? date('M d, Y', strtotime($received)) : (isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'N/A') }}</p>
                            </td>
                            <td>
                                <div class="flex gap-2 items-center">
                                    @if($itemId && !$published)
                                        <button class="fm-btn primary sm"
                                                onclick="askPublish({{ $itemId }}, @js($item['title'] ?? 'this item'), '{{ $acquisition }}', '{{ $publicPrice }}', JSON.parse(atob('{{ base64_encode(json_encode($item)) }}')))">
                                            <i class="fas fa-store"></i>Publish
                                        </button>
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
                                    <button class="row-btn view-item-btn" title="View details" data-item-data="{{ base64_encode(json_encode($item)) }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="row-btn edit-item-btn" title="Edit item &amp; price" data-item-data="{{ base64_encode(json_encode($item)) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No acquired items found</p>
                                    <span>Items appear here once they have been received at the counter.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Publishing: the price, and what it means, before it goes live. -->
<div id="publishModal" class="modal-overlay">
    <div class="modal">
        <div class="flex items-center justify-between mb-4">
            <h3>Publish to the catalog</h3>
            <button onclick="closePublishModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="space-y-4">
            <p class="cell-sub" id="publishSubtitle" style="margin: 0;"></p>

            <div>
                <label class="fm-label" for="publishPrice">Public selling price (₱)</label>
                <input id="publishPrice" type="number" step="0.01" min="0" class="fm-input" placeholder="e.g. 350.00">
                <p class="cell-sub" style="margin-top: 5px;">What a buyer pays. The markup and the reward points come from it.</p>
            </div>

            <div id="publishPreview" class="fm-card-body" style="background: var(--surface-sunk); border-radius: 8px; font-size: 13px; padding: 12px;"></div>

            {{--
                The last look before it goes on sale. A student's title and
                snapshots are not always what the catalog should show, and
                noticing that at this moment used to mean closing the dialog,
                finding the editor and starting again. It is optional and
                folded away, because most items go up as they are.
            --}}
            <div style="border-top: 1px solid var(--line); padding-top: 12px;">
                <button type="button" class="fm-btn ghost sm" id="publishEditToggle" onclick="togglePublishEdit()" aria-expanded="false">
                    <i class="fas fa-pen"></i>Edit information
                </button>
                <p class="cell-sub" style="margin: 6px 0 0;">Optional. The title, the description and the photos buyers will see.</p>

                <div id="publishEdit" style="display: none; margin-top: 12px;" class="space-y-3">
                    <div>
                        <label class="fm-label" for="publishTitle">Title</label>
                        <input id="publishTitle" class="fm-input" maxlength="255">
                    </div>

                    <div>
                        <label class="fm-label" for="publishDescription">Description</label>
                        <textarea id="publishDescription" class="fm-input" rows="3" maxlength="1000"></textarea>
                    </div>

                    <div>
                        <label class="fm-label" for="publishPhotoFiles">Photos</label>
                        <div id="publishPhotos" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;"></div>
                        <input id="publishPhotoFiles" type="file" accept="image/*" multiple class="fm-input">
                        <p class="cell-sub" id="publishPhotoNote" style="margin-top: 6px;">Choosing a photo adds it straight away. The first one is the cover.</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closePublishModal()" class="fm-btn ghost">Cancel</button>
                <button type="button" onclick="confirmPublish()" class="fm-btn primary" id="publishButton">
                    <i class="fas fa-store"></i>Publish
                </button>
            </div>
        </div>
    </div>
</div>

<!-- The counter's own photographs, for the row that claims the payout. -->
<div id="proofModal" class="modal-overlay">
    <div class="modal">
        <div class="flex items-center justify-between mb-4">
            <h3>Payment proof</h3>
            <button onclick="closeProofModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="proofContent" class="space-y-4"></div>
    </div>
</div>

@push('styles')
<style>
    .modal-lg { max-width: 900px; width: 90%; }
</style>
@endpush

@include('admin.partials.item-workflow')

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

// ── Publishing ───────────────────────────────────────────────────────────

let publishingItemId = null;
let publishingAcquisition = null;
let publishingItem = null;
let previewTimer = null;

function askPublish(itemId, title, acquisition, currentPrice, item) {
    publishingItemId = itemId;
    publishingAcquisition = acquisition ? Number(acquisition) : null;
    publishingItem = item || {};

    // The optional half starts folded: most items go up as they are.
    document.getElementById('publishEdit').style.display = 'none';
    document.getElementById('publishEditToggle').setAttribute('aria-expanded', 'false');
    document.getElementById('publishTitle').value = publishingItem.title || title || '';
    document.getElementById('publishDescription').value = publishingItem.description || '';
    document.getElementById('publishPhotoFiles').value = '';

    document.getElementById('publishSubtitle').textContent = publishingAcquisition
        ? `${title} · the store paid ${fmPeso(acquisition)} for it.`
        : `${title}`;

    const field = document.getElementById('publishPrice');
    field.value = currentPrice || '';
    document.getElementById('publishPreview').innerHTML =
        '<span style="color: var(--ink-500);">Enter a price to see the markup and the points the buyer earns.</span>';

    document.getElementById('publishModal').classList.add('active');
    setTimeout(() => field.focus(), 60);

    if (field.value) refreshPreview();
}

function closePublishModal() {
    document.getElementById('publishModal').classList.remove('active');
    publishingItemId = null;
}

/**
 * The preview comes from the server, not from arithmetic here: it is produced
 * by the same code that publishes, so the reward points shown are the ones
 * that get stored, and anything blocking the sale is named before the click.
 */
async function refreshPreview() {
    const price = (document.getElementById('publishPrice').value || '').trim();
    const host = document.getElementById('publishPreview');

    if (!publishingItemId || !price || Number(price) <= 0) {
        host.innerHTML = '<span style="color: var(--ink-500);">Enter a price to see the markup and the points the buyer earns.</span>';
        return;
    }

    host.innerHTML = '<span class="loading-spinner"></span>';

    try {
        const response = await fetch(
            `${API}/admin/items/${publishingItemId}/publish-preview?public_price=${encodeURIComponent(price)}`,
            { headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } }
        );
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        const data = payload.data || {};
        const blockers = data.blockers || [];
        const row = (label, value) => `
            <div style="display: flex; justify-content: space-between; gap: 12px; padding: 3px 0;">
                <span style="color: var(--ink-500);">${label}</span><span style="font-weight: 600;">${value}</span>
            </div>`;

        host.innerHTML = `
            ${row('Public price', fmPeso(data.public_price))}
            ${row('Acquisition', fmPeso(data.acquisition_price))}
            ${row('Markup', fmPeso(data.markup))}
            ${row('Buyer earns', `${data.reward_points || 0} point(s)`)}
            ${blockers.length
                ? `<p style="color: var(--danger); margin: 8px 0 0;">${blockers.join('<br>')}</p>`
                : '<p style="color: var(--success); margin: 8px 0 0;">Ready to publish.</p>'}`;

        document.getElementById('publishButton').disabled = blockers.length > 0;
    } catch (error) {
        host.innerHTML = `<span style="color: var(--danger);">${error.message}</span>`;
    }
}

document.getElementById('publishPrice').addEventListener('input', () => {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(refreshPreview, 350);
});

/** The optional half of the dialog, folded away until it is wanted. */
function togglePublishEdit() {
    const panel = document.getElementById('publishEdit');
    const toggle = document.getElementById('publishEditToggle');
    const opening = panel.style.display === 'none';

    panel.style.display = opening ? 'block' : 'none';
    toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');

    if (opening) loadPublishPhotos();
}

/**
 * The listing's photos, managed in place. They are saved as they are added
 * and removed rather than with the Publish button, because the server refuses
 * to remove the last one and that answer belongs next to the photo.
 */
async function loadPublishPhotos() {
    const host = document.getElementById('publishPhotos');
    if (!publishingItemId) return;

    host.innerHTML = '<span class="cell-sub">Loading photos…</span>';

    try {
        const response = await fetch(`${API}/admin/items/${publishingItemId}/photos`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        const photos = payload.data || [];

        host.innerHTML = photos.length ? photos.map(photo => `
            <div style="position: relative; width: 84px; height: 84px;">
                <img src="${photo.photo_url}" alt="" style="width: 84px; height: 84px; object-fit: cover; border-radius: 8px; border: 1px solid var(--line);">
                <button type="button" title="Remove photo" onclick="removePublishPhoto(${Number(photo.photo_id)})"
                        style="position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border-radius: 50%; border: none; background: rgba(17,24,39,0.75); color: white; cursor: pointer; font-size: 12px;">&times;</button>
            </div>`).join('') : '<span class="cell-sub">No photos yet.</span>';
    } catch (error) {
        host.innerHTML = `<span style="color: var(--danger); font-size: 12.5px;">${error.message}</span>`;
    }
}

/**
 * Choosing the files is the whole action.
 *
 * There used to be an Add photos button beside the picker, so a photo could
 * sit chosen but not added, and the panel showed the old pictures as if
 * nothing had been picked. One step, and the thumbnails answer.
 */
document.getElementById('publishPhotoFiles').addEventListener('change', uploadPublishPhotos);

async function uploadPublishPhotos() {
    const input = document.getElementById('publishPhotoFiles');
    const note = document.getElementById('publishPhotoNote');
    const files = Array.from(input.files || []);

    if (!files.length) return;

    note.textContent = files.length === 1 ? 'Adding the photo…' : `Adding ${files.length} photos…`;

    const body = new FormData();
    files.forEach(file => body.append('photos[]', file, file.name));

    try {
        const response = await fetch(`${API}/admin/items/${publishingItemId}/photos`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
            body,
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        input.value = '';
        note.textContent = 'Choosing a photo adds it straight away. The first one is the cover.';
        showToast(files.length === 1 ? 'Photo added' : `${files.length} photos added`, 'success');
        loadPublishPhotos();
    } catch (error) {
        input.value = '';
        note.textContent = 'Choosing a photo adds it straight away. The first one is the cover.';
        showToast(`Could not add the photos: ${error.message}`, 'error');
    }
}

async function removePublishPhoto(photoId) {
    try {
        const response = await fetch(`${API}/admin/items/${publishingItemId}/photos/${photoId}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        showToast('Photo removed', 'success');
        loadPublishPhotos();
    } catch (error) {
        showToast(`Could not remove the photo: ${error.message}`, 'error');
    }
}

/** The title and description, saved before the item goes live, if they changed. */
async function savePublishDetails() {
    const title = document.getElementById('publishTitle').value.trim();
    const description = document.getElementById('publishDescription').value.trim();
    const body = {};

    if (title && title !== (publishingItem.title || '')) body.title = title;
    if (description !== (publishingItem.description || '')) body.description = description;

    if (Object.keys(body).length === 0) return true;

    const response = await fetch(`${API}/admin/items/${publishingItemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
        },
        body: JSON.stringify(body),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

    return true;
}

async function confirmPublish() {
    if (!publishingItemId) return;

    const price = (document.getElementById('publishPrice').value || '').trim();

    if (!price || isNaN(Number(price)) || Number(price) <= 0) {
        showToast('Enter the selling price first, e.g. 350 or 349.50.', 'error');
        document.getElementById('publishPrice').focus();
        return;
    }

    const button = document.getElementById('publishButton');
    button.disabled = true;
    button.textContent = 'Publishing…';

    try {
        await savePublishDetails();

        const response = await fetch(`${API}/admin/items/${publishingItemId}/publish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ public_price: price }),
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        showToast('Published. Buyers can see it now.', 'success');
        closePublishModal();
        setTimeout(() => location.reload(), 900);
    } catch (error) {
        showToast(`Could not publish: ${error.message}`, 'error');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-store"></i>Publish';
    }
}

// ── The counter's proof ──────────────────────────────────────────────────

/**
 * The two photographs taken when the item came in: the item itself, and the
 * seller holding their cash. The row says the seller was paid; this is what
 * that claim rests on.
 */
document.querySelectorAll('.proof-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        showProof(
            this.dataset.title,
            this.dataset.itemPhoto,
            this.dataset.payoutPhoto,
            this.dataset.amount,
        );
    });
});

function showProof(title, itemPhoto, payoutPhoto, amount) {
    const shot = (label, url, missing) => url
        ? `<div>
                <p class="cell-sub">${label}</p>
                <a href="${url}" target="_blank" rel="noopener" title="Open full size">
                    <img src="${url}" alt="${label}" style="width: 100%; border-radius: 10px; margin-top: 4px;">
                </a>
           </div>`
        : `<div>
                <p class="cell-sub">${label}</p>
                <p style="font-size: 13px; color: var(--ink-500); margin: 4px 0 0;">${missing}</p>
           </div>`;

    document.getElementById('proofContent').innerHTML = `
        <p style="margin: 0; font-size: 13.5px;">${title} · ${amount} handed to the seller.</p>
        ${shot('The seller being paid', payoutPhoto, 'No photo was taken of the payout.')}
        ${shot('The item received', itemPhoto, 'No photo was taken of the item.')}
        <p class="cell-sub" style="margin: 0;">Taken at the counter when the turnover was recorded.</p>`;

    document.getElementById('proofModal').classList.add('active');
}

function closeProofModal() {
    document.getElementById('proofModal').classList.remove('active');
}

document.getElementById('proofModal').addEventListener('click', function (e) {
    if (e.target === this) closeProofModal();
});

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

document.getElementById('publishModal').addEventListener('click', function (e) {
    if (e.target === this) closePublishModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    closePublishModal();
    closeProofModal();
});
</script>
@endpush

@include('admin.partials.item-view')
@include('admin.partials.item-edit')
@endsection
