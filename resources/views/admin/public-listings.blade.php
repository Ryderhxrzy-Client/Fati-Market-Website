@extends('layouts.admin-dashboard')

@section('title', 'Public Listings')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Public Listings</h3>
            <p class="text-sm text-gray-600">Items currently available for public purchase</p>
        </div>
        <div class="flex gap-3">
            <input type="search" placeholder="Search items..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Items Grid/List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Item</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Seller</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Markup Points</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Listed Date</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($items as $item)
                        @php
                            $imageUrl = !empty($item['photos']) && is_array($item['photos']) ? $item['photos'][0] : null;
                            $sellerEmail = $item['seller_email'] ?? 'N/A';
                            $points = $item['price_points'] ?? 'N/A';
                            $itemId = $item['item_id'] ?? $item['id'] ?? 'N/A';
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if(!empty($imageUrl))
                                        <img src="{{ $imageUrl }}" alt="{{ $item['title'] ?? 'Item' }}" class="w-10 h-10 rounded object-cover" loading="lazy">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $item['title'] ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ $itemId }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ $sellerEmail }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-green-600">{{ $item['markup_points'] ?? 0 }} pts</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button class="px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded transition view-item-btn" title="View details" data-item-id="{{ $itemId }}" data-item-data="{{ base64_encode(json_encode($item)) }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="px-3 py-1 text-sm font-medium text-orange-600 hover:bg-orange-50 rounded transition edit-item-btn" title="Edit item" data-item-id="{{ $itemId }}" data-item-data="{{ base64_encode(json_encode($item)) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No public listings found</p>
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
            <h3 class="text-lg font-bold text-gray-900">Item Details</h3>
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
            <h3 class="text-lg font-bold text-gray-900">Edit Item</h3>
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
                    <label class="block text-sm font-medium text-gray-900 mb-1">Status</label>
                    <select id="editStatus" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="private">Private</option>
                        <option value="public">Public</option>
                        <option value="acquired">Acquired</option>
                        <option value="reserved">Reserved</option>
                        <option value="sold">Sold</option>
                    </select>
                </div>

                <div class="flex gap-3 justify-end pt-4">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
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
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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
                    <p class="text-sm text-gray-600">Title</p>
                    <p class="text-lg font-semibold text-gray-900">${item.title || 'N/A'}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Seller Email</p>
                    <p class="text-gray-900">${item.seller_email || 'N/A'}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Price Points</p>
                    <p class="text-gray-900">${item.price_points || 0} pts</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Status</p>
                    <p class="text-gray-900 capitalize">${item.status || 'N/A'}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Description</p>
                    <p class="text-gray-900">${item.description || 'N/A'}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Created</p>
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
        document.getElementById('editStatus').value = item.status || 'public';

        // Populate item details
        const detailsHtml = `
            <div>
                ${imageUrl ? `<img src="${imageUrl}" alt="${item.title}" class="w-full h-48 rounded object-cover mb-4">` : ''}
            </div>
            <div>
                <p class="text-sm text-gray-600">Title</p>
                <p class="font-semibold text-gray-900">${item.title || 'N/A'}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Seller Email</p>
                <p class="text-gray-900">${item.seller_email || 'N/A'}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Price Points</p>
                <p class="text-gray-900">${item.price_points || 0} pts</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Description</p>
                <p class="text-gray-900">${item.description || 'N/A'}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Created</p>
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

        try {
            const response = await fetch(`https://fati-api.alertaraqc.com/api/admin/items/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${sessionStorage.getItem('admin_token') || ''}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status
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
