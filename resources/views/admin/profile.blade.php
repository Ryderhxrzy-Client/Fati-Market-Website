@extends('layouts.admin-dashboard')

@section('title', 'Profile')
@section('subtitle', 'Your account on Fati Market')

@section('content')
@php
    $profilePic = session('admin_profile_picture');
    $firstName  = $adminData['first_name'] ?? session('admin_first_name', '');
    $lastName   = $adminData['last_name'] ?? session('admin_last_name', '');
    $fullName   = trim($firstName . ' ' . $lastName) ?: ($adminData['name'] ?? 'Administrator');
    $email      = $adminData['email'] ?? session('admin_data.email', '');
    $initial    = strtoupper(substr($firstName ?: 'A', 0, 1));
    $joined     = !empty($adminData['created_at']) ? date('M d, Y', strtotime($adminData['created_at'])) : null;
@endphp

<div class="space-y-6">
    {{--
        Real data only. The old page showed a made-up points breakdown,
        "Two-factor: not enabled", "Last login: today at 10:30" and a form
        that saved nothing. This mirrors the mobile profile: who is signed
        in, a photo that can actually be changed, the live points balance,
        and the store settings the admin reaches from here.
    --}}
    <div class="fm-card">
        <div style="height: 120px; background: linear-gradient(135deg, var(--brand-800), var(--brand-500));"></div>
        <div class="px-6 pb-6">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4" style="margin-top: -48px;">
                <div class="flex items-end gap-4">
                    @if($profilePic)
                        <img src="{{ $profilePic }}" alt="" class="avatar" style="width: 96px; height: 96px; border: 4px solid #fff; box-shadow: var(--shadow); font-size: 32px;">
                    @else
                        <div class="avatar" style="width: 96px; height: 96px; border: 4px solid #fff; box-shadow: var(--shadow); font-size: 32px;">{{ $initial }}</div>
                    @endif
                    <div style="padding-bottom: 6px;">
                        <h2 style="font-size: 20px; font-weight: 650; margin: 0;">{{ $fullName }}</h2>
                        <p style="margin: 2px 0 0; font-size: 13px; color: var(--ink-500);">Store administrator{{ $joined ? ' · since ' . $joined : '' }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.profile.picture') }}" enctype="multipart/form-data" id="photoForm" class="flex items-center gap-2">
                    @csrf
                    <input type="file" name="profile_picture" id="photoInput" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="submitPhoto()">
                    <button type="button" class="fm-btn ghost" onclick="document.getElementById('photoInput').click()" id="photoButton">
                        <i class="fas fa-camera"></i>Change photo
                    </button>
                </form>
            </div>

            @if(session('profile_success'))
                <p role="status" class="fm-badge success mt-4">{{ session('profile_success') }}</p>
            @endif
            @error('profile_picture')
                <p role="alert" class="fm-badge danger mt-4">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-6">
            <section class="fm-card">
                <div class="fm-card-head"><h4>Account</h4></div>
                <div class="fm-divided">
                    <div class="flex items-center gap-4 px-5 py-3">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-user"></i></div>
                        <div class="min-w-0">
                            <p class="cell-sub">Full name</p>
                            <p class="cell-title">{{ $fullName }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 px-5 py-3">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-envelope"></i></div>
                        <div class="min-w-0">
                            <p class="cell-sub">Email</p>
                            <p class="cell-title truncate">{{ $email ?: '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 px-5 py-3">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-id-badge"></i></div>
                        <div class="min-w-0">
                            <p class="cell-sub">Role</p>
                            <p class="cell-title">Administrator{{ !empty($adminData['user_id']) ? ' · user #' . $adminData['user_id'] : '' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="fm-card">
                <div class="fm-card-head"><h4>Store</h4></div>
                <div class="fm-divided">
                    <a href="{{ route('admin.settings') }}#store-hours" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-clock"></i></div>
                        <div class="min-w-0 flex-1">
                            <p class="cell-title">Store hours &amp; booking slots</p>
                            <p class="cell-sub">Opening times, open days and slot length</p>
                        </div>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.settings') }}#gcash" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-wallet"></i></div>
                        <div class="min-w-0 flex-1">
                            <p class="cell-title">GCash payment settings</p>
                            <p class="cell-sub">Account name, mobile number and payment QR</p>
                        </div>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.settings') }}#location" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-location-dot"></i></div>
                        <div class="min-w-0 flex-1">
                            <p class="cell-title">Store location</p>
                            <p class="cell-sub">Hollywood Terraces, Sumulong Hwy, Antipolo</p>
                        </div>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.transactions.history') }}" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-receipt"></i></div>
                        <div class="min-w-0 flex-1">
                            <p class="cell-title">Transactions</p>
                            <p class="cell-sub">Every order - pending, reserved, unpaid, completed</p>
                        </div>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.students') }}" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-users"></i></div>
                        <div class="min-w-0 flex-1">
                            <p class="cell-title">Students</p>
                            <p class="cell-sub">Approve, decline or block student accounts</p>
                        </div>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="stat-label">Points balance</p>
                        <div class="stat-value" id="walletPoints"><span class="loading-spinner"></span></div>
                        <p class="cell-sub" style="margin-top: 6px;">The store account's wallet, as the app shows it</p>
                    </div>
                    <div class="stat-icon" style="background: var(--reward-bg); color: var(--reward);">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>

            <div class="fm-card fm-card-body">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="fm-btn danger w-full">
                        <i class="fas fa-arrow-right-from-bracket"></i>Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function submitPhoto() {
        const input = document.getElementById('photoInput');
        if (!input.files.length) return;
        if (input.files[0].size > 5 * 1024 * 1024) {
            showToast('Choose a photo up to 5 MB.', 'error');
            input.value = '';
            return;
        }
        const button = document.getElementById('photoButton');
        button.disabled = true;
        button.innerHTML = '<span class="loading-spinner"></span>Uploading…';
        document.getElementById('photoForm').submit();
    }

    (async function () {
        const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
        const target = document.getElementById('walletPoints');
        try {
            const response = await fetch('https://fati-api.alertaraqc.com/api/wallet', {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
            const points = Number(payload.data?.wallet_points ?? 0);
            target.textContent = points.toLocaleString();
        } catch (error) {
            target.textContent = '—';
        }
    })();
</script>
@endpush
@endsection
