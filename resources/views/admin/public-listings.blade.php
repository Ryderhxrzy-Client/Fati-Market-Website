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
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Owner</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Category</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Price</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Listed Date</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $item['title'] ?? 'Untitled' }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ $item['id'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ $item['owner_name'] ?? 'Unknown' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $item['category'] ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-green-600">${{ number_format($item['price'] ?? 0, 2) }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button class="px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded transition" title="View details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="px-3 py-1 text-sm font-medium text-red-600 hover:bg-red-50 rounded transition" title="Mark as reserved">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    <button class="px-3 py-1 text-sm font-medium text-orange-600 hover:bg-orange-50 rounded transition" title="Mark as sold">
                                        <i class="fas fa-check-circle"></i>
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

@push('scripts')
<script>
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
</script>
@endpush
@endsection
