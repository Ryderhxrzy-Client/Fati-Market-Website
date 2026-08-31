@extends('layouts.admin-dashboard')

@section('title', 'Sold Items')
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
                        <th>Seller</th>
                        <th>Sold for</th>
                        <th>Created</th>
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
                                {{-- The store owns it now; the student is
                                     provenance, not the seller. --}}
                                <p class="cell-title">Ofelia Store</p>
                                <p class="cell-sub">from {{ $sellerEmail }}</p>
                            </td>
                            <td>
                                <p class="cell-title money">{{ \App\Support\Peso::format($price) }}</p>
                                <p class="cell-sub">markup {{ \App\Support\Peso::format($item['markup'] ?? null) }}</p>
                            </td>
                            <td>
                                <p>{{ isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'N/A' }}</p>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <button class="row-btn view-item-btn" title="View details" data-item-id="{{ $itemId }}" data-item-data="{{ base64_encode(json_encode($item)) }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="row-btn edit-item-btn" title="Edit item" data-item-id="{{ $itemId }}" data-item-data="{{ base64_encode(json_encode($item)) }}">
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
<div id="viewModal" class="modal-overlay">
    <div class="modal">
        <div class="flex items-center justify-between mb-4">
            <h3>Item Details</h3>
            <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="viewContent" class="space-y-4">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal modal-lg">
        <div class="flex items-center justify-between mb-4">
            <h3>Edit Item</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- Item Details (Left) -->
            <div id="editItemDetails" class="space-y-4 border-r border-gray-200 pr-6">
                <!-- Details populated by JavaScript -->
            </div>

            <!-- Edit Form (Right) -->
            <form id="editForm" class="space-y-4">
                <input type="hidden" id="editItemId">

                <div>
                    <label class="fm-label">Status</label>
                    <select id="editStatus" class="fm-input">
                        <option value="private">Private</option>
                        <option value="public">Public</option>
                        <option value="acquired">Acquired</option>
                        <option value="reserved">Reserved</option>
                        <option value="sold">Sold</option>
                    </select>
                </div>

                <div>
                    <label class="fm-label">Public selling price (₱)</label>
                    <input type="text" inputmode="decimal" id="editMarkupPoints" class="fm-input" placeholder="e.g. 250.00">
                    <p class="cell-sub" style="margin-top: 5px;">What a buyer pays. Use the workflow panel to set the acquisition price.</p>
                </div>

                <div class="flex gap-3 justify-end pt-4">
                    <button type="button" onclick="closeEditModal()" class="fm-btn ghost">
                        Cancel
                    </button>
                    <button type="submit" class="fm-btn primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
            showViewModal(itemData);
        });
    });

    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemData = JSON.parse(atob(this.getAttribute('data-item-data')));
            showEditModal(itemData);
        });
    });

    function showViewModal(item) {
        const content = document.getElementById('viewContent');
        const imageUrl = !Array.isArray(item.photos) || item.photos.length === 0 ? null : item.photos[0];

        content.innerHTML = `
            <div class="space-y-4">
                ${imageUrl ? `<img src="${imageUrl}" alt="${item.title}" class="w-full h-64 rounded object-cover">` : ''}
                <div>
                    <p>Title</p>
                    <p class="text-lg font-semibold text-gray-900">${item.title || 'N/A'}</p>
                </div>
                <div>
                    <p>Seller Email</p>
                    <p class="text-gray-900">${item.seller_email || 'N/A'}</p>
                </div>
                <div>
                    <p>Price Points</p>
                    <p class="cell-title money">${fmPeso(item.seller_asking_price)}</p>
                </div>
                <div>
                    <p>Markup Points</p>
                    <p class="cell-title money">${fmPeso(item.public_price)}</p>
                </div>
                <div>
                    <p>Status</p>
                    <p class="text-gray-900 capitalize">${item.status || 'N/A'}</p>
                </div>
                <div>
                    <p>Description</p>
                    <p class="text-gray-900">${item.description || 'N/A'}</p>
                </div>
                <div>
                    <p>Created</p>
                    <p class="text-gray-900">${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</p>
                </div>
            </div>
        `;
        document.getElementById('viewModal').classList.add('active');
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.remove('active');
    }

    function showEditModal(item) {
        const imageUrl = !Array.isArray(item.photos) || item.photos.length === 0 ? null : item.photos[0];

        document.getElementById('editItemId').value = item.item_id;
        document.getElementById('editStatus').value = item.status || 'sold';
        document.getElementById('editMarkupPoints').value = item.public_price || '';

        // Populate item details
        const detailsHtml = `
            <div>
                ${imageUrl ? `<img src="${imageUrl}" alt="${item.title}" class="w-full h-48 rounded object-cover mb-4">` : ''}
            </div>
            <div>
                <p>Title</p>
                <p class="font-semibold text-gray-900">${item.title || 'N/A'}</p>
            </div>
            <div>
                <p>Seller Email</p>
                <p class="text-gray-900">${item.seller_email || 'N/A'}</p>
            </div>
            <div>
                <p>Price Points</p>
                <p class="cell-title money">${fmPeso(item.seller_asking_price)}</p>
            </div>
            <div>
                <p>Current Markup</p>
                <p class="cell-title money">${fmPeso(item.public_price)}</p>
            </div>
            <div>
                <p>Description</p>
                <p class="text-gray-900">${item.description || 'N/A'}</p>
            </div>
            <div>
                <p>Created</p>
                <p class="text-gray-900">${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</p>
            </div>
        `;

        document.getElementById('editItemDetails').innerHTML = detailsHtml;
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    document.getElementById('editForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const itemId = document.getElementById('editItemId').value;
        const status = document.getElementById('editStatus').value;
        const markupPoints = document.getElementById('editMarkupPoints').value;

        try {
            const response = await fetch(`https://fati-api.alertaraqc.com/api/admin/items/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status,
                    public_price: String(markupPoints || '')
                })
            });

            const data = await response.json();

            if (response.ok) {
                showToast('Item updated successfully', 'success');
                closeEditModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to update item', 'error');
            }
        } catch (error) {
            showToast('Error: ' + error.message, 'error');
        }
    });

    document.getElementById('viewModal').addEventListener('click', function(e) {
        if (e.target === this) closeViewModal();
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
</script>
@endpush
@endsection
