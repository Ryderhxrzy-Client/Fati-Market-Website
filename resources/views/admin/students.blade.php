@extends('layouts.admin-dashboard')

@section('title', 'User Management')
@section('subtitle', 'Manage registered users and account status')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="fm-toolbar">
        <select id="statusFilter" class="fm-input">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="blocked">Blocked</option>
        </select>
        <select id="methodFilter" class="fm-input">
            <option value="">All Registration Methods</option>
            <option value="Google">Google</option>
            <option value="Email & Password">Email & Password</option>
        </select>
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search users by name, email or ID..." id="searchInput" class="fm-input"></span>
    </div>

    <!-- Users List -->
    <div class="fm-card">
        <div class="fm-table-wrap">
            <table class="fm-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Registered Via</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Points</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsList">
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-3 block"></i>
                            <p class="text-gray-500">Loading users...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Block / Unblock Modal -->
<div id="verificationActionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="modal modal-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="verificationActionModalTitle">Action</h3>
            <button onclick="closeVerificationActionModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 gap-2">
                <p><span class="font-semibold">User:</span> <span id="actionModalUserName"></span></p>
                <p><span class="font-semibold">Email:</span> <span id="actionModalUserEmail"></span></p>
                <p><span class="font-semibold">Current Status:</span> <span id="actionModalUserStatus"></span></p>
                <p class="cell-sub">User ID: <span id="actionModalStudentId"></span></p>
            </div>

            <div id="actionReasonWrapper">
                <label class="fm-label">Reason for blocking</label>
                <textarea id="actionReasonInput" class="fm-input" placeholder="Enter reason for blocking this user..." rows="3"></textarea>
                <p id="actionReasonError" class="hidden text-sm text-red-600 mt-1">Reason is required to block an account.</p>
            </div>

            <p id="actionUnblockMessage" class="hidden text-sm text-gray-600">
                Are you sure you want to unblock this user? Their account will be reactivated and they will be able to sign in again.
            </p>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeVerificationActionModal()" class="fm-btn ghost">
                    Cancel
                </button>
                <button type="button" id="actionConfirmBtn" onclick="submitVerificationAction()" class="fm-btn primary">
                    Confirm
                </button>
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
    attachEventListeners();
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
        filterAndRender();
    } catch (error) {
        console.error('Error loading users:', error);
        document.getElementById('studentsList').innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <i class="fas fa-exclamation-circle text-4xl text-red-300 mb-3 block"></i>
                    <p class="text-red-500 font-medium">Error loading users</p>
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
                <td colspan="7">
                    <div class="fm-empty">
                        <i class="fas fa-users-slash"></i>
                        <p>No users found</p>
                        <span>No registered users match your criteria.</span>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    list.innerHTML = students.map(student => {
        const fullName = `${student.first_name || ''} ${student.last_name || ''}`.trim() || ('User #' + student.user_id);
        const isBlocked = student.status === 'blocked';
        const method = student.registration_method || (student.email && student.email.endsWith('@fatima.edu.ph') ? 'Google' : 'Email & Password');
        const initials = ((student.first_name?.[0] || 'U') + (student.last_name?.[0] || '')).toUpperCase();

        const methodBadge = method === 'Google'
            ? `<span class="fm-badge brand" style="display:inline-flex;align-items:center;gap:6px;"><i class="fab fa-google"></i> Google</span>`
            : `<span class="fm-badge" style="display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-envelope"></i> Email &amp; Password</span>`;

        const statusBadge = isBlocked
            ? `<span class="fm-badge danger" style="display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-ban"></i> Blocked</span>`
            : `<span class="fm-badge success" style="display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-check-circle"></i> Active</span>`;

        const actionButton = isBlocked
            ? `<button class="fm-btn ghost text-green-600 hover:text-green-700 font-medium" style="color:var(--success, #16a34a);border-color:currentColor;" onclick="openActionModal('${student.user_id}', 'unblock')">
                <i class="fas fa-unlock"></i> Unblock
               </button>`
            : `<button class="fm-btn ghost text-red-600 hover:text-red-700 font-medium" style="color:var(--danger, #dc2626);border-color:currentColor;" onclick="openActionModal('${student.user_id}', 'block')">
                <i class="fas fa-ban"></i> Block
               </button>`;

        const registeredDate = student.registered_date ? new Date(student.registered_date).toLocaleDateString() : '—';

        return `
            <tr class="hover:bg-gray-50 transition" data-status="${student.status}" data-user-id="${student.user_id}">
                <td>
                    <div class="flex items-center gap-3">
                        ${student.profile_picture ?
                            `<img src="${student.profile_picture}" alt="${fullName}" class="w-10 h-10 rounded-full object-cover">` :
                            `<div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center text-white font-bold text-sm">${initials}</div>`
                        }
                        <div>
                            <p class="cell-title font-semibold">${fullName}</p>
                            <p class="cell-sub">ID: #${student.user_id}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <p class="text-sm text-gray-700">${student.email}</p>
                </td>
                <td>
                    ${methodBadge}
                </td>
                <td>
                    ${statusBadge}
                </td>
                <td>
                    <p class="text-sm text-gray-600">${registeredDate}</p>
                </td>
                <td>
                    <p class="text-sm font-medium text-green-700">${student.wallet_points ?? 0} pts</p>
                </td>
                <td>
                    <div class="flex gap-2">
                        ${actionButton}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

let currentAction = null;

function openActionModal(userId, action) {
    const student = allStudents.find(s => String(s.user_id) === String(userId));
    if (!student) return;

    currentAction = action;

    const fullName = `${student.first_name || ''} ${student.last_name || ''}`.trim() || ('User #' + student.user_id);
    document.getElementById('actionModalUserName').textContent = fullName;
    document.getElementById('actionModalUserEmail').textContent = student.email;
    document.getElementById('actionModalUserStatus').textContent = student.status === 'blocked' ? 'Blocked' : 'Active';
    document.getElementById('actionModalStudentId').textContent = student.user_id;

    document.getElementById('actionReasonError').classList.add('hidden');
    document.getElementById('actionReasonInput').value = '';

    const titleMap = {
        block: 'Block User Account',
        unblock: 'Unblock User Account'
    };

    document.getElementById('verificationActionModalTitle').textContent = titleMap[action] || 'Action';

    const reasonWrapper = document.getElementById('actionReasonWrapper');
    const unblockMessage = document.getElementById('actionUnblockMessage');
    const confirmBtn = document.getElementById('actionConfirmBtn');

    if (action === 'block') {
        reasonWrapper.classList.remove('hidden');
        unblockMessage.classList.add('hidden');
        confirmBtn.className = 'fm-btn danger';
        confirmBtn.innerHTML = '<i class="fas fa-ban"></i> Block Account';
    } else {
        reasonWrapper.classList.add('hidden');
        unblockMessage.classList.remove('hidden');
        confirmBtn.className = 'fm-btn primary';
        confirmBtn.innerHTML = '<i class="fas fa-unlock"></i> Unblock Account';
    }

    document.getElementById('verificationActionModal').classList.remove('hidden');
}

function closeVerificationActionModal() {
    document.getElementById('verificationActionModal').classList.add('hidden');
}

async function submitVerificationAction() {
    const action = currentAction;
    const userId = document.getElementById('actionModalStudentId').textContent;
    const reason = document.getElementById('actionReasonInput').value?.trim() || '';

    if (action === 'block' && !reason) {
        document.getElementById('actionReasonError').classList.remove('hidden');
        return;
    }

    try {
        const endpoint = `https://fati-api.alertaraqc.com/api/admin/students/${userId}/${action}`;
        const body = (action === 'block') ? JSON.stringify({ reason }) : undefined;

        const response = await fetch(endpoint, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            ...(body ? { body } : {})
        });

        const contentType = response.headers.get('content-type') || '';
        const payload = contentType.includes('application/json') ? await response.json().catch(() => null) : null;

        if (!response.ok) {
            const apiMsg = payload?.message || payload?.error || `HTTP ${response.status}`;
            throw new Error(apiMsg);
        }

        if (action === 'block') showToast('User blocked successfully', 'error');
        if (action === 'unblock') showToast('User unblocked successfully', 'success');

        closeVerificationActionModal();
        await loadStudents();

    } catch (error) {
        console.error('Error submitting action:', error);
        showToast('Error: ' + error.message, 'error');
    }
}

function filterAndRender() {
    const searchTerm = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('statusFilter')?.value || '';
    const methodVal = document.getElementById('methodFilter')?.value || '';

    let filtered = allStudents;

    if (statusVal) {
        if (statusVal === 'blocked') {
            filtered = filtered.filter(s => s.status === 'blocked');
        } else if (statusVal === 'active') {
            filtered = filtered.filter(s => s.status !== 'blocked');
        } else {
            filtered = filtered.filter(s => s.status === statusVal);
        }
    }

    if (methodVal) {
        filtered = filtered.filter(s => s.registration_method === methodVal);
    }

    if (searchTerm) {
        filtered = filtered.filter(s => {
            const fullName = `${s.first_name || ''} ${s.last_name || ''}`.toLowerCase();
            const email = (s.email || '').toLowerCase();
            return fullName.includes(searchTerm) || email.includes(searchTerm) || String(s.user_id).includes(searchTerm);
        });
    }

    renderStudents(filtered);
}

function attachEventListeners() {
    document.getElementById('searchInput')?.addEventListener('input', filterAndRender);
    document.getElementById('statusFilter')?.addEventListener('change', filterAndRender);
    document.getElementById('methodFilter')?.addEventListener('change', filterAndRender);
}

function showToast(message, type) {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
@endpush
@endsection
