@extends('layouts.admin-dashboard')

@section('title', 'Sales Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Sales Report</h3>
            <p class="text-sm text-gray-600">View items acquired and sold</p>
        </div>
        <div class="flex gap-3">
            <input type="search" placeholder="Search reports..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Reports Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($reportData as $report)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">{{ $report['label'] ?? 'Sales Metric' }}</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $report['value'] ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                        <i class="fas fa-chart-bar text-green-600"></i>
                    </div>
                </div>
                @if(isset($report['description']))
                    <p class="text-xs text-gray-500">{{ $report['description'] }}</p>
                @endif
                @if(isset($report['period']))
                    <p class="text-xs text-gray-400 mt-2">Period: {{ $report['period'] }}</p>
                @endif
            </div>
        @empty
            <div class="col-span-2 bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                <p class="text-gray-500">No sales data available</p>
            </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.bg-white').forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
</script>
@endpush
@endsection
