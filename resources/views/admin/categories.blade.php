@extends('layouts.admin-dashboard')

@section('title', 'Categories Management')

@section('content')
<div class="space-y-6">
    <!-- Add Category Button -->
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">All Categories</h2>
        <button onclick="openAddCategoryModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
            <i class="fas fa-plus mr-2"></i>Add Category
        </button>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($categories as $category)
            <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                <div class="h-32 bg-gradient-to-br from-green-400 to-blue-500"></div>
                <div class="p-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ $category['name'] ?? 'Unknown' }}</h3>
                    <p class="text-sm text-gray-600 mt-2">{{ $category['description'] ?? 'No description' }}</p>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-box mr-2"></i>
                        {{ $category['items_count'] ?? 0 }} items
                    </p>
                    <div class="flex gap-2 mt-4">
                        <button class="flex-1 px-3 py-2 text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition text-sm font-medium">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button class="flex-1 px-3 py-2 text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition text-sm font-medium">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-tags text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 mt-2">No categories found</p>
                    <button onclick="openAddCategoryModal()" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm">
                        <i class="fas fa-plus mr-2"></i>Create First Category
                    </button>
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">Add New Category</h3>
            <button onclick="closeCategoryModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                <input type="text" placeholder="e.g., Electronics" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea placeholder="Brief description of the category..." rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Icon</label>
                <div class="flex gap-2">
                    <input type="text" placeholder="Icon name (Font Awesome)" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-icon text-gray-600"></i>
                    </button>
                </div>
            </div>

            <div class="flex gap-2 pt-4">
                <button type="button" onclick="closeCategoryModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium text-gray-700">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAddCategoryModal() {
        document.getElementById('categoryModal').classList.add('active');
    }

    function closeCategoryModal() {
        document.getElementById('categoryModal').classList.remove('active');
    }

    // Close modal on outside click
    document.getElementById('categoryModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCategoryModal();
        }
    });
</script>
@endpush
@endsection
