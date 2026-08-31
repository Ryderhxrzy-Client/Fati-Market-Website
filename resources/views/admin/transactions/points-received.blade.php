@extends('layouts.admin-dashboard')

@section('title', 'Points Received')
@section('subtitle', 'View all points received by users')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="fm-toolbar">
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search transactions..." id="searchInput" class="fm-input"></span>
    </div>

    <!-- Transactions Table -->
    <div class="fm-card">
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>User Email</th>
                        <th>Points Change</th>
                        <th>Reason</th>
                        <th>Related Item</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        @php
                            $pointsChange = $transaction['points_change'] ?? 0;
                            $userEmail = is_array($transaction['user']) ? ($transaction['user']['email'] ?? 'Unknown') : ($transaction['user_email'] ?? 'Unknown');
                            $itemTitle = is_array($transaction['related_item']) ? ($transaction['related_item']['title'] ?? 'N/A') : ($transaction['related_item_title'] ?? 'N/A');
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-bold">
                                        {{ substr($userEmail, 0, 1) }}
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $userEmail }}</span>
                                </div>
                            </td>
                            <td>
                                <p class="text-sm font-semibold {{ $pointsChange > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $pointsChange > 0 ? '+' : '' }}{{ $pointsChange }} pts
                                </p>
                            </td>
                            <td>
                                <p>{{ $transaction['reason'] ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p>{{ $itemTitle }}</p>
                            </td>
                            <td>
                                <p>{{ isset($transaction['created_at']) ? date('M d, Y H:i', strtotime($transaction['created_at'])) : 'N/A' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="fm-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No points received found</p>
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
