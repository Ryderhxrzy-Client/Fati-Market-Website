@extends('layouts.admin-dashboard')

@section('title', 'Profit Summary')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Profit Summary</h3>
            <p class="text-sm text-gray-600">View profit metrics and transaction statistics</p>
        </div>
    </div>

    <!-- Profit Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Profit -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Profit</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $transactions['total_profit'] ?? '0' }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Monthly Profit -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Monthly Profit</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $transactions['monthly_profit'] ?? '0' }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-chart-line text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Completed Transactions -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Completed Transactions</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ $transactions['completed_transactions'] ?? '0' }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Average Per Transaction -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Avg Per Transaction</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2">{{ $transactions['avg_per_transaction'] ?? '0' }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-calculator text-orange-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Per Card Breakdown -->
    @if(isset($transactions['per_card']) && !empty($transactions['per_card']))
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900">Profit Per Card</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Card</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Total Profit</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Completed Txns</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Avg Per Txn</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($transactions['per_card'] as $card)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $card['card_name'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-green-600">{{ $card['total_profit'] ?? '0' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ $card['completed_transactions'] ?? '0' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-orange-600">{{ $card['avg_per_transaction'] ?? '0' }}</p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@endsection
