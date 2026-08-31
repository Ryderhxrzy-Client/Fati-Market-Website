@extends('layouts.admin-dashboard')

@section('title', 'Categories Report')
@section('subtitle', 'View most sold categories and performance')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <!-- Most Sold Category Card -->
    @if(!empty($reportData['most_sold']))
    <div class="fm-card fm-card-body">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-gray-600 font-medium">Most Sold Category</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">{{ $reportData['most_sold']['category_name'] ?? 'N/A' }}</p>
                <div class="mt-4 space-y-2">
                    <p>Items Sold: <span class="font-semibold text-gray-900">{{ $reportData['most_sold']['items_sold'] ?? 0 }}</span></p>
                    <p>Total Markup Profit: <span class="font-semibold text-green-600">{{ $reportData['most_sold']['total_markup_profit'] ?? 0 }} pts</span></p>
    </div>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                <i class="fas fa-chart-area text-purple-600"></i>
            </div>
        </div>
    </div>
    @endif

    <!-- All Categories Table -->
    <div class="fm-card">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">All Categories</h4>
        </div>
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Items Sold</th>
                        <th>Total Markup</th>
                        <th>Avg Markup per Item</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['categories'] ?? [] as $category)
                        <tr>
                            <td>
                                <p class="text-sm font-medium text-gray-900">{{ $category['category_name'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p>{{ $category['items_sold'] ?? 0 }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-green-600">{{ $category['total_markup_profit'] ?? 0 }} pts</p>
                            </td>
                            <td>
                                <p>{{ number_format($category['average_markup_per_item'] ?? 0, 2) }} pts</p>
                            </td>
                            <td>
                                @php
                                    $status = $category['items_sold'] > 0 ? 'active' : 'inactive';
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'inactive' => 'bg-gray-100 text-gray-800',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No category data available</p>
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

@push('scripts')
<script>
    document.getElementById('searchInput')?.addEventListener('keyup', function(e) {
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
