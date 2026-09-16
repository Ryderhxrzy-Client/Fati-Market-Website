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
                <div class="fm-card-head">
                    <div>
                        <h4>Account</h4>
                        <p class="cell-sub" style="margin-top: 2px;">Your name is what students see in chat and what the activity log records</p>
                    </div>
                </div>

                {{--
                    The name was printed here and nothing more, so one typed
                    wrong at registration followed the admin onto every screen.
                --}}
                <div class="fm-card-body" style="border-bottom: 1px solid var(--line);">
                    @error('first_name')
                        <p role="alert" class="fm-badge danger" style="margin-bottom: 12px;">{{ $message }}</p>
                    @enderror
                    @error('last_name')
                        <p role="alert" class="fm-badge danger" style="margin-bottom: 12px;">{{ $message }}</p>
                    @enderror

                    <form method="POST" action="{{ route('admin.profile.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @csrf
                        <div>
                            <label class="fm-label" for="firstName">First name</label>
                            <input id="firstName" name="first_name" class="fm-input" maxlength="100" required
                                   value="{{ old('first_name', $firstName) }}">
                        </div>
                        <div>
                            <label class="fm-label" for="lastName">Last name</label>
                            <input id="lastName" name="last_name" class="fm-input" maxlength="100" required
                                   value="{{ old('last_name', $lastName) }}">
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="fm-btn primary"><i class="fas fa-save"></i>Save name</button>
                        </div>
                    </form>
                </div>

                <div class="fm-divided">
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

            {{--
                Changing the password of the account signed in here.

                There was no way to do this from the console at all: the only
                routes to a new password were the forgotten-password email and
                a dialog in the app that only students could reach. The API
                checks the old password and ends every other session.
            --}}
            <section class="fm-card" id="security">
                <div class="fm-card-head">
                    <div>
                        <h4>Password</h4>
                        <p class="cell-sub" style="margin-top: 2px;">Changing it signs your other devices out</p>
                    </div>
                </div>

                <div class="fm-card-body">
                    @error('password')
                        <p role="alert" class="fm-badge danger" style="margin-bottom: 12px;">{{ $message }}</p>
                    @enderror
                    @error('current_password')
                        <p role="alert" class="fm-badge danger" style="margin-bottom: 12px;">{{ $message }}</p>
                    @enderror

                    <form method="POST" action="{{ route('admin.profile.password') }}" id="passwordForm" class="space-y-3">
                        @csrf

                        <div>
                            <label class="fm-label" for="currentPassword">Current password</label>
                            <input id="currentPassword" name="current_password" type="password" class="fm-input"
                                   autocomplete="current-password" required>
                        </div>

                        <div>
                            <label class="fm-label" for="newPassword">New password</label>
                            <input id="newPassword" name="password" type="password" class="fm-input"
                                   autocomplete="new-password" required oninput="checkPassword()">
                        </div>

                        <div>
                            <label class="fm-label" for="confirmPassword">Repeat the new password</label>
                            <input id="confirmPassword" name="password_confirmation" type="password" class="fm-input"
                                   autocomplete="new-password" required oninput="checkPassword()">
                        </div>

                        <ul id="passwordRules" style="list-style: none; padding: 0; margin: 4px 0 0; font-size: 12.5px; color: var(--ink-500);">
                            <li data-rule="length"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> At least 8 characters</li>
                            <li data-rule="upper"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> One uppercase letter</li>
                            <li data-rule="lower"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> One lowercase letter</li>
                            <li data-rule="digit"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> One number</li>
                            <li data-rule="special"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> One special character (&#64;$!%*?&amp;)</li>
                            <li data-rule="match"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> Both new passwords match</li>
                        </ul>

                        <button type="submit" class="fm-btn primary" id="passwordButton" disabled style="margin-top: 6px;">
                            <i class="fas fa-key"></i>Change password
                        </button>
                    </form>
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
                    <a href="{{ route('admin.transactions.manage') }}" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition">
                        <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-receipt"></i></div>
                        <div class="min-w-0 flex-1">
                            <p class="cell-title">Manage orders</p>
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
    /**
     * The same rules the API applies, checked as they are typed, so a refused
     * password is refused before the round trip rather than after it.
     */
    function checkPassword() {
        const password = document.getElementById('newPassword').value;
        const confirmation = document.getElementById('confirmPassword').value;

        const met = {
            length: password.length >= 8,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            digit: /\d/.test(password),
            special: /[@$!%*?&]/.test(password),
            match: password !== '' && password === confirmation,
        };

        document.querySelectorAll('#passwordRules li').forEach((item) => {
            const ok = met[item.dataset.rule];
            item.style.color = ok ? 'var(--success)' : 'var(--ink-500)';
            item.querySelector('i').className = ok ? 'fas fa-circle-check' : 'fas fa-circle';
            item.querySelector('i').style.fontSize = ok ? '11px' : '6px';
        });

        document.getElementById('passwordButton').disabled = Object.values(met).some((ok) => !ok);
    }

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
