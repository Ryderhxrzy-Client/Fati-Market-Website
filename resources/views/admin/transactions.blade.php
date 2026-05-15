@extends('layouts.admin-dashboard')

@section('title', 'Transactions')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 font-medium">Total Transactions</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">2,543</p>
            <p class="text-xs text-gray-500 mt-2">All time</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 font-medium">Completed</p>
            <p class="text-3xl font-bold text-green-600 mt-2">2,198</p>
            <p class="text-xs text-gray-500 mt-2">86.4% success rate</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 font-medium">Pending</p>
            <p class="text-3xl font-bold text-orange-600 mt-2">247</p>
            <p class="text-xs text-gray-500 mt-2">Awaiting completion</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 font-medium">Failed</p>
            <p class="text-3xl font-bold text-red-600 mt-2">98</p>
            <p class="text-xs text-gray-500 mt-2">3.9% failed rate</p>
        </div>
    </div>

    <!-- Header with Filters -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Transaction History</h3>
            <p class="text-sm text-gray-600">Detailed list of all platform transactions</p>
        </div>
        <div class="flex gap-3">
            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Statuses</option>
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
            </select>
            <input type="search" placeholder="Search transactions..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Transaction ID</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">User</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Type</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Amount</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Date</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 transition" data-status="{{ $transaction['status'] ?? 'pending' }}">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $transaction['id'] ?? 'TXN-0000' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-bold">
                                        {{ substr($transaction['user_name'] ?? 'U', 0, 1) }}
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $transaction['user_name'] ?? 'Unknown' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ ucfirst($transaction['type'] ?? 'payment') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-green-600">${{ number_format($transaction['amount'] ?? 0, 2) }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $status = $transaction['status'] ?? 'pending';
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-orange-100 text-orange-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ isset($transaction['created_at']) ? date('M d, Y H:i', strtotime($transaction['created_at'])) : 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button class="px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded transition" title="View details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="px-3 py-1 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded transition" title="Download receipt">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
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
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            if (row.querySelector('td')) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        });
    });

    document.getElementById('statusFilter').addEventListener('change', function(e) {
        const selectedStatus = e.target.value;
        document.querySelectorAll('tbody tr').forEach(row => {
            if (row.querySelector('td')) {
                const status = row.dataset.status;
                row.style.display = !selectedStatus || status === selectedStatus ? '' : 'none';
            }
        });
    });
</script>
@endpush
@endsection
