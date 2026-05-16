@extends('layouts.admin-dashboard')

@section('title', 'User Management')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">User Management</h3>
            <p class="text-sm text-gray-600">Manage users and verify their profiles</p>
        </div>
        <div class="flex gap-3">
            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="blocked">Blocked</option>
            </select>
            <input type="search" placeholder="Search users..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Students List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">User</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Email</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Verification</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Joined</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Points</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="studentsList">
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-3 block"></i>
                            <p class="text-gray-500">Loading students...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Verification Modal -->
<div id="verificationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900">Verification Document</h3>
            <button onclick="closeVerificationModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <img id="verificationImage" src="" alt="Verification" class="w-full rounded-lg">
            <div class="mt-4 space-y-2">
                <p><span class="font-semibold">User:</span> <span id="modalUserName"></span></p>
                <p><span class="font-semibold">Email:</span> <span id="modalUserEmail"></span></p>
                <p><span class="font-semibold">Type:</span> <span id="modalVerificationType"></span></p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let token = null;
let allStudents = [];

function getToken() {
    const metaToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content');
    if (metaToken && metaToken.trim()) {
        return metaToken;
    }
    return sessionStorage.getItem('admin_token') || localStorage.getItem('admin_token');
}

document.addEventListener('DOMContentLoaded', async function() {
    token = getToken();
    await loadStudents();
});

async function loadStudents() {
    try {
        const response = await fetch('https://fati-api.alertaraqc.com/api/admin/students', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        allStudents = Array.isArray(data) ? data : (data.data || []);
        renderStudents(allStudents);
    } catch (error) {
        console.error('Error loading students:', error);
        document.getElementById('studentsList').innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <i class="fas fa-exclamation-circle text-4xl text-red-300 mb-3 block"></i>
                    <p class="text-red-500 font-medium">Error loading students</p>
                    <p class="text-red-400 text-sm">${error.message}</p>
                </td>
            </tr>
        `;
    }
}

function renderStudents(students) {
    const list = document.getElementById('studentsList');

    if (students.length === 0) {
        list.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                    <p class="text-gray-500">No students found</p>
                </td>
            </tr>
        `;
        return;
    }

    list.innerHTML = students.map(student => {
        const fullName = `${student.first_name} ${student.last_name}`;
        const status = student.status || 'pending';
        const statusColors = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'approved': 'bg-green-100 text-green-800',
            'blocked': 'bg-red-100 text-red-800'
        };
        const verificationStatus = student.is_verified ? 'Verified' : 'Unverified';
        const verificationStatusColor = student.is_verified ? 'text-green-600' : 'text-orange-600';

        return `
            <tr class="hover:bg-gray-50 transition" data-status="${status}" data-student-id="${student.student_verification_id}">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        ${student.profile_picture ?
                            `<img src="${student.profile_picture}" alt="${fullName}" class="w-10 h-10 rounded-full object-cover">` :
                            `<div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-bold text-sm">${student.first_name[0]}${student.last_name[0]}</div>`
                        }
                        <div>
                            <p class="font-medium text-gray-900">${fullName}</p>
                            <p class="text-xs text-gray-500">ID: ${student.user_id}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm text-gray-600">${student.email}</p>
                </td>
                <td class="px-6 py-4">
                    <div>
                        <p class="text-sm font-medium ${verificationStatusColor}">${verificationStatus}</p>
                        <button onclick="showVerificationModal('${student.student_verification_id}')" class="text-xs text-blue-600 hover:text-blue-800 mt-1">
                            View Document
                        </button>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusColors[status] || 'bg-gray-100 text-gray-800'}">
                        ${status.charAt(0).toUpperCase() + status.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm text-gray-600">${new Date(student.registered_date).toLocaleDateString()}</p>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm font-medium text-green-600">${student.wallet_points} pts</p>
                </td>
                <td class="px-6 py-4">
                    <div class="flex gap-2">
                        ${status === 'pending' ? `
                            <button class="px-3 py-1 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded transition approve-btn" title="Approve" onclick="approveStudent('${student.student_verification_id}')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="px-3 py-1 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded transition decline-btn" title="Decline" onclick="declineStudent('${student.student_verification_id}')">
                                <i class="fas fa-times"></i> Decline
                            </button>
                        ` : ''}
                        ${status !== 'blocked' ? `
                            <button class="px-3 py-1 text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 rounded transition block-btn" title="Block" onclick="blockStudent('${student.student_verification_id}')">
                                <i class="fas fa-ban"></i> Block
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    attachEventListeners();
}

function showVerificationModal(studentId) {
    const student = allStudents.find(s => s.student_verification_id == studentId);
    if (!student) return;

    document.getElementById('verificationImage').src = student.verification_document;
    document.getElementById('modalUserName').textContent = `${student.first_name} ${student.last_name}`;
    document.getElementById('modalUserEmail').textContent = student.email;
    document.getElementById('modalVerificationType').textContent = student.verification_type === 'student_id' ? 'Student ID' : 'ID';
    document.getElementById('verificationModal').classList.remove('hidden');
}

function closeVerificationModal() {
    document.getElementById('verificationModal').classList.add('hidden');
}

async function approveStudent(studentId) {
    if (!confirm('Approve this student?')) return;

    try {
        const response = await fetch(`https://fati-api.alertaraqc.com/api/admin/students/${studentId}/approve`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        showToast('Student approved successfully', 'success');
        await loadStudents();
    } catch (error) {
        console.error('Error approving student:', error);
        showToast('Error approving student: ' + error.message, 'error');
    }
}

async function declineStudent(studentId) {
    if (!confirm('Decline this student?')) return;

    try {
        const response = await fetch(`https://fati-api.alertaraqc.com/api/admin/students/${studentId}/decline`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        showToast('Student declined', 'info');
        await loadStudents();
    } catch (error) {
        console.error('Error declining student:', error);
        showToast('Error declining student: ' + error.message, 'error');
    }
}

async function blockStudent(studentId) {
    if (!confirm('Block this student?')) return;

    try {
        const response = await fetch(`https://fati-api.alertaraqc.com/api/admin/students/${studentId}/block`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        showToast('Student blocked', 'error');
        await loadStudents();
    } catch (error) {
        console.error('Error blocking student:', error);
        showToast('Error blocking student: ' + error.message, 'error');
    }
}

function attachEventListeners() {
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const filtered = allStudents.filter(student => {
            const fullName = `${student.first_name} ${student.last_name}`.toLowerCase();
            const email = student.email.toLowerCase();
            return fullName.includes(searchTerm) || email.includes(searchTerm);
        });
        renderStudents(filtered);
    });
}

document.getElementById('statusFilter').addEventListener('change', function(e) {
    const selectedStatus = e.target.value;
    const filtered = selectedStatus ?
        allStudents.filter(s => s.status === selectedStatus) :
        allStudents;
    renderStudents(filtered);
});

function showToast(message, type) {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
@endpush
@endsection
