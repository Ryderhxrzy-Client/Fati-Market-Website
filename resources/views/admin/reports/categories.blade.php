@extends('layouts.admin-dashboard')

@section('title', 'Categories Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Categories Report</h3>
            <p class="text-sm text-gray-600">View most sold categories</p>
        </div>
        <div class="flex gap-3">
            <input type="search" placeholder="Search categories..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Categories List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Category</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Items Sold</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Revenue</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Percentage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reportData as $category)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $category['name'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ $category['items_sold'] ?? 0 }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-green-600">{{ $category['revenue'] ?? 0 }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $category['percentage'] ?? 0 }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600 whitespace-nowrap">{{ $category['percentage'] ?? 0 }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No category data available</p>
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
            if (row.querySelector('td')) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        });
    });
</script>
@endpush
@endsection
