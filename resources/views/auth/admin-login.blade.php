@extends('layouts.admin-auth')

@section('title', 'Sign in')

@section('content')
{{--
    A two-panel sign-in: the brand sits on the left on a wide screen and
    collapses to a slim header on a phone, so the form is never pushed below
    the fold the way the old 300px banner pushed it.
--}}
<div class="min-h-screen lg:grid lg:grid-cols-2">

    <!-- ── Brand panel ─────────────────────────────────────────────── -->
    <div class="relative overflow-hidden px-8 py-10 lg:px-14 lg:py-16 flex flex-col justify-between"
         style="background: linear-gradient(160deg, var(--brand-900) 0%, var(--brand-700) 60%, var(--brand-600) 100%);">

        <div aria-hidden="true"
             style="position: absolute; width: 420px; height: 420px; right: -140px; top: -120px;
                    border-radius: 50%; background: rgba(255,255,255,0.05);"></div>
        <div aria-hidden="true"
             style="position: absolute; width: 300px; height: 300px; left: -110px; bottom: -110px;
                    border-radius: 50%; background: rgba(255,255,255,0.04);"></div>

        <div class="relative flex items-center gap-3">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,0.14);
                        border: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center;
                        justify-content: center;">
                <i class="fas fa-store text-white text-lg"></i>
            </div>
            <div>
                <div class="text-white font-semibold">Fati Market</div>
                <div class="text-white/55 text-[11px] uppercase tracking-wider">Admin console</div>
            </div>
        </div>

        <div class="relative hidden lg:block mt-16">
            <h1 class="text-white text-[30px] font-semibold leading-tight">
                Run the store<br>from one place.
            </h1>
            <p class="text-white/65 text-[14px] mt-3 max-w-sm">
                Review offers, price and publish stock, settle GCash payments and
                answer students - the same tools as the mobile app.
            </p>

            <ul class="mt-8 space-y-3 text-white/75 text-[13.5px]">
                <li class="flex items-center gap-3">
                    <i class="fas fa-circle-check" style="color: #7BD3A4;"></i>
                    Acquire, price and publish items
                </li>
                <li class="flex items-center gap-3">
                    <i class="fas fa-circle-check" style="color: #7BD3A4;"></i>
                    Verify GCash receipts and complete orders
                </li>
                <li class="flex items-center gap-3">
                    <i class="fas fa-circle-check" style="color: #7BD3A4;"></i>
                    Chat with buyers and sellers per item
                </li>
            </ul>
        </div>

        <p class="relative text-white/40 text-[11.5px] mt-10 hidden lg:block">
            Ofelia Store &middot; Our Lady of Fatima University
        </p>
    </div>

    <!-- ── Form panel ──────────────────────────────────────────────── -->
    <div class="flex items-center justify-center px-5 py-10 lg:py-16">
        <div class="w-full" style="max-width: 400px;">

            <h2 class="text-[24px] font-semibold">Welcome back</h2>
            <p class="text-[14px] mt-1" style="color: var(--ink-500);">
                Sign in with your administrator account.
            </p>

            @if ($errors->any())
                <div role="alert" class="mt-5 flex gap-3 p-3"
                     style="background: #FCE8E6; border: 1px solid #F3C7C3; border-radius: var(--radius); color: var(--danger);">
                    <i class="fas fa-circle-exclamation mt-0.5"></i>
                    <div class="text-[13px]">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="/" class="mt-6" id="loginForm">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-[13px] font-semibold mb-2" style="color: var(--ink-700);">
                        Email address
                    </label>
                    <div class="relative">
                        <i class="fas fa-envelope auth-icon"></i>
                        <input type="email" id="email" name="email" required autofocus
                               autocomplete="username"
                               class="auth-input {{ $errors->has('email') ? 'invalid' : '' }}"
                               placeholder="you@fatima.edu.ph"
                               value="{{ old('email') }}">
                    </div>
                    @error('email')
                        <p class="text-[12px] mt-1.5" style="color: var(--danger);">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-[13px] font-semibold" style="color: var(--ink-700);">
                            Password
                        </label>
                        <a href="#" class="text-[12.5px] font-semibold no-underline hover:underline"
                           style="color: var(--brand-600);">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <i class="fas fa-lock auth-icon"></i>
                        <input type="password" id="password" name="password" required
                               autocomplete="current-password"
                               class="auth-input {{ $errors->has('password') ? 'invalid' : '' }}"
                               style="padding-right: 42px;"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()"
                                aria-label="Show password"
                                id="passwordToggle"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
                                       background: none; border: none; cursor: pointer; color: var(--ink-400);
                                       padding: 4px;">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-[12px] mt-1.5" style="color: var(--danger);">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 mb-6 cursor-pointer text-[13.5px]" style="color: var(--ink-500);">
                    <input type="checkbox" name="remember" style="width: 15px; height: 15px; accent-color: var(--brand-600);">
                    Keep me signed in
                </label>

                <button type="submit" class="auth-btn" id="loginButton">
                    <span id="buttonText">Sign in</span>
                    <span id="buttonSpinner" style="display: none;">
                        <span style="display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.35);
                                     border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite;"></span>
                    </span>
                </button>
            </form>

            <p class="text-center text-[12px] mt-8" style="color: var(--ink-400);">
                Students use the Fati Market mobile app.
            </p>
        </div>
    </div>
</div>

@if (session('login_success'))
<div class="fixed inset-0 flex items-center justify-center z-[1000] p-6"
     style="background: rgba(12, 48, 33, 0.45); backdrop-filter: blur(3px);" id="successDialog">
    <div style="background: var(--surface); border-radius: 16px; padding: 30px; max-width: 380px; width: 100%;
                text-align: center; box-shadow: var(--shadow-lg);">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--brand-100);
                    display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i class="fas fa-circle-check" style="color: var(--brand-600); font-size: 26px;"></i>
        </div>
        <h3 class="text-[18px] font-semibold mb-1">You are signed in</h3>
        <p class="text-[13.5px] mb-6" style="color: var(--ink-500);">Taking you to the dashboard…</p>
        <button class="auth-btn" onclick="window.location.href='/dashboard'">Go to dashboard</button>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush

@push('scripts')
<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('passwordToggleIcon');
        const shown = input.type === 'text';

        input.type = shown ? 'password' : 'text';
        icon.classList.toggle('fa-eye', shown);
        icon.classList.toggle('fa-eye-slash', !shown);
        document.getElementById('passwordToggle')
            .setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
    }

    // Signing in calls the API, which takes a moment; say so rather than
    // letting the page sit there looking ignored.
    document.getElementById('loginForm').addEventListener('submit', function () {
        const button = document.getElementById('loginButton');

        button.disabled = true;
        document.getElementById('buttonText').textContent = 'Signing in';
        document.getElementById('buttonSpinner').style.display = 'inline-block';
    });

    document.addEventListener('DOMContentLoaded', function () {
        @if (session('error'))
            showToast('error', @json(session('error')));
        @endif

        @if (session('success'))
            showToast('success', @json(session('success')));
        @endif
    });
</script>
@endpush
