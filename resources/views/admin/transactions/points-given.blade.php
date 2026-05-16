@extends('layouts.admin-dashboard')

@section('title', 'Points Given')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Points Given</h3>
            <p class="text-sm text-gray-600">View all points given by users</p>
        </div>
        <div class="flex gap-3">
            <input type="search" placeholder="Search transactions..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">User Email</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Points Change</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Reason</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Related Item</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        @php
                            $pointsChange = $transaction['points_change'] ?? 0;
                            $userEmail = is_array($transaction['user']) ? ($transaction['user']['email'] ?? 'Unknown') : ($transaction['user_email'] ?? 'Unknown');
                            $itemTitle = is_array($transaction['related_item']) ? ($transaction['related_item']['title'] ?? 'N/A') : ($transaction['related_item_title'] ?? 'N/A');
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-bold">
                                        {{ substr($userEmail, 0, 1) }}
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $userEmail }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold {{ $pointsChange > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $pointsChange > 0 ? '+' : '' }}{{ $pointsChange }} pts
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ $transaction['reason'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ $itemTitle }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ isset($transaction['created_at']) ? date('M d, Y H:i', strtotime($transaction['created_at'])) : 'N/A' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No points given found</p>
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
