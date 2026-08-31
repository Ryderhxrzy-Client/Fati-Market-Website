@extends('layouts.admin-dashboard')

@section('title', 'Transactions')
@section('subtitle', 'View detailed transaction records')

@section('content')
<div class="space-y-6">
    <!-- Header with Transaction Type Selector -->
    <div class="fm-toolbar">
        <select id="transactionTypeDropdown" onchange="changeTransactionType(this.value)" class="fm-input">
            <option value="history" @if($type === 'history') selected @endif>Transaction History</option>
            <option value="points_given" @if($type === 'points_given') selected @endif>Points Given</option>
            <option value="points_received" @if($type === 'points_received') selected @endif>Points Received</option>
            <option value="cash" @if($type === 'cash') selected @endif>Cash Transactions</option>
            <option value="trade" @if($type === 'trade') selected @endif>Trade Transactions</option>
            <option value="profit" @if($type === 'profit') selected @endif>Profit Summary</option>
        </select>
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search transactions..." id="searchInput" class="fm-input"></span>
    </div>

    <!-- Transactions Table -->
    <div class="fm-card">
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        @if($type === 'profit')
                            <th>Summary Type</th>
                            <th>Total Profit</th>
                            <th>Period</th>
                        @else
                            <th>Transaction ID</th>
                            <th>
                                @if($type === 'points_given')
                                    Recipient
                                @elseif($type === 'points_received')
                                    Sender
                                @else
                                    User
                                @endif
                            </th>
                            <th>
                                @if($type === 'points_given' || $type === 'points_received')
                                    Points
                                @else
                                    Amount
                                @endif
                            </th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        @if($type === 'profit')
                            <tr>
                                <td>
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction['type'] ?? 'Summary' }}</p>
                                </td>
                                <td>
                                    <p class="text-sm font-semibold text-green-600">{{ $transaction['total_profit'] ?? '0' }}</p>
                                </td>
                                <td>
                                    <p>{{ $transaction['period'] ?? 'N/A' }}</p>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction['id'] ?? 'TXN-0000' }}</p>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-bold">
                                            {{ substr($transaction['user_name'] ?? $transaction['recipient_name'] ?? $transaction['sender_name'] ?? 'U', 0, 1) }}
                                        </div>
                                        <span class="text-sm text-gray-900">{{ $transaction['user_name'] ?? $transaction['recipient_name'] ?? $transaction['sender_name'] ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-sm font-semibold @if($type === 'points_given' || $type === 'points_received') text-blue-600 @else text-green-600 @endif">
                                        @if($type === 'points_given' || $type === 'points_received')
                                            {{ $transaction['points'] ?? $transaction['amount'] ?? 0 }} pts
                                        @else
                                            {{ $transaction['amount'] ?? 0 }}
                                        @endif
                                    </p>
                                </td>
                                <td>
                                    @php
                                        $status = $transaction['status'] ?? 'pending';
                                        $statusColors = [
                                            'completed' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-orange-100 text-orange-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                            'success' => 'bg-green-100 text-green-800',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td>
                                    <p>{{ isset($transaction['created_at']) ? date('M d, Y H:i', strtotime($transaction['created_at'])) : (isset($transaction['date']) ? $transaction['date'] : 'N/A') }}</p>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded transition" title="View details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td @if($type === 'profit') colspan="3" @else colspan="6" @endif class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No transactions found</p>
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
    function changeTransactionType(type) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('type', type);
        window.location.href = currentUrl.toString();
    }

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
