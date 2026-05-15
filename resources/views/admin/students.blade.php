@extends('layouts.admin-dashboard')

@section('title', 'Student Management')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Student Management</h3>
            <p class="text-sm text-gray-600">Manage users and verify their profiles</p>
        </div>
        <div class="flex gap-3">
            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending Verification</option>
                <option value="approved">Approved</option>
                <option value="declined">Declined</option>
                <option value="blocked">Blocked</option>
            </select>
            <input type="search" placeholder="Search students..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Students List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Student</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Email</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Joined</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Points</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($students as $student)
                        <tr class="hover:bg-gray-50 transition" data-status="{{ $student['verification_status'] ?? 'pending' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-bold">
                                        {{ substr($student['name'] ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $student['name'] ?? 'Unknown' }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ $student['id'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ $student['email'] ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $status = $student['verification_status'] ?? 'pending';
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'declined' => 'bg-red-100 text-red-800',
                                        'blocked' => 'bg-gray-100 text-gray-800',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600">{{ isset($student['created_at']) ? date('M d, Y', strtotime($student['created_at'])) : 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-green-600">{{ $student['points'] ?? 0 }} pts</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button class="px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded transition view-student-btn" title="View details" data-student-id="{{ $student['id'] ?? '' }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if(($student['verification_status'] ?? 'pending') === 'pending')
                                        <button class="px-3 py-1 text-sm font-medium text-green-600 hover:bg-green-50 rounded transition approve-btn" title="Approve" data-student-id="{{ $student['id'] ?? '' }}">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="px-3 py-1 text-sm font-medium text-red-600 hover:bg-red-50 rounded transition decline-btn" title="Decline" data-student-id="{{ $student['id'] ?? '' }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                    @if(($student['verification_status'] ?? 'pending') !== 'blocked')
                                        <button class="px-3 py-1 text-sm font-medium text-red-600 hover:bg-red-50 rounded transition block-btn" title="Block" data-student-id="{{ $student['id'] ?? '' }}">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No students found</p>
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

    // Action buttons handlers
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            if (confirm('Approve this student?')) {
                showToast('Student approved successfully', 'success');
            }
        });
    });

    document.querySelectorAll('.decline-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            if (confirm('Decline this student?')) {
                showToast('Student declined', 'info');
            }
        });
    });

    document.querySelectorAll('.block-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            if (confirm('Block this student?')) {
                showToast('Student blocked', 'error');
            }
        });
    });

    document.querySelectorAll('.view-student-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            console.log('View student details:', studentId);
        });
    });
</script>
@endpush
@endsection
