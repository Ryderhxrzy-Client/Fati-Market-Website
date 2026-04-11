@extends('layouts.admin-auth')

@section('title', 'Admin Login')

@section('content')
<div class="min-h-screen flex flex-col">
    <!-- Header with gradient background -->
    <div class="bg-gradient-to-b from-[#1A5C38] to-[#2E7D52] h-[300px] flex items-center justify-center">
        <div class="flex flex-col items-center gap-3">
            <div class="w-[72px] h-[72px] rounded-full bg-white/15 border-2 border-[#D4A017] flex items-center justify-center">
                <i class="fas fa-shopping-cart text-white text-[38px]"></i>
            </div>
            <h1 class="text-white text-[26px] font-bold tracking-[1px]">Fati-Market</h1>
            <p class="text-white/80 text-[13px]">Admin Login</p>
        </div>
    </div>
    
    <!-- Form card -->
    <div class="bg-white rounded-[20px] shadow-2xl mx-5 -mt-7 p-8 max-w-[600px] mx-auto">
        <div class="text-center mb-6">
            <h2 class="text-[22px] font-bold text-[#1C1B1F] mb-1">Welcome Back</h2>
            <p class="text-[14px] text-[#6B6B6B]">Log in to your admin account</p>
        </div>
        
                
        <form method="POST" action="/">
            @csrf
            
            <!-- Email field -->
            <div class="mb-4">
                <label class="block mb-2 font-semibold text-[#1C1B1F] text-[14px]" for="email">Email Address</label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-[#1A5C38] text-[20px]"></i>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="w-full pl-12 pr-4 py-3 border-2 border-[#EEEEE8] rounded-[12px] text-[16px] transition-colors focus:outline-none focus:border-[#1A5C38] {{ $errors->has('email') ? 'border-red-500' : '' }}"
                        placeholder="Enter your email"
                        value="{{ old('email') }}"
                        required
                    >
                </div>
                @error('email')
                    <div class="text-red-500 text-[12px] mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            
            <!-- Password field -->
            <div class="mb-4">
                <label class="block mb-2 font-semibold text-[#1C1B1F] text-[14px]" for="password">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[#1A5C38] text-[20px]"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full pl-12 pr-12 py-3 border-2 border-[#EEEEE8] rounded-[12px] text-[16px] transition-colors focus:outline-none focus:border-[#1A5C38] {{ $errors->has('password') ? 'border-red-500' : '' }}"
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 bg-none border-none text-[#1A5C38] text-[20px] p-0" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>
                @error('password')
                    <div class="text-red-500 text-[12px] mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            
            <!-- Forgot password link -->
            <div class="text-right mb-5">
                <a href="#" class="text-[#1A5C38] text-[14px] font-semibold no-underline hover:underline">Forgot Password?</a>
            </div>
            
            <!-- Remember me -->
            <div class="flex items-center gap-2 mb-5">
                <input type="checkbox" id="remember" name="remember" class="w-auto">
                <label for="remember" class="m-0 text-[14px] text-[#6B6B6B]">Remember me</label>
            </div>
            
            <!-- Login button -->
            <button type="submit" class="w-full h-[52px] bg-[#1A5C38] text-white border-none rounded-[12px] text-[16px] font-bold cursor-pointer transition-all hover:bg-[#2E7D52] hover:-translate-y-0.5 hover:shadow-lg flex items-center justify-center gap-2" id="loginButton">
                <span id="buttonText">Login</span>
                <div class="hidden" id="buttonSpinner">
                    <div class="w-[22px] h-[22px] border-2 border-transparent border-t-2 border-white rounded-full animate-spin"></div>
                </div>
            </button>
        </form>
    </div>
    
    <div class="flex-1"></div>
</div>

<!-- Success dialog (hidden by default) -->
@if (session('login_success'))
<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-[1000]" id="successDialog">
    <div class="bg-white rounded-[16px] p-8 max-w-[400px] text-center shadow-2xl">
        <div class="w-[72px] h-[72px] rounded-full bg-[#1A5C38]/10 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-shopping-cart text-[#1A5C38] text-[36px]"></i>
        </div>
        <h3 class="text-[20px] font-bold text-[#1C1B1F] mb-2">Login Successful</h3>
        <p class="text-[14px] text-[#6B6B6B] mb-6">Welcome back! Redirecting to dashboard...</p>
        <button class="w-full h-[52px] bg-[#1A5C38] text-white border-none rounded-[12px] text-[16px] font-bold cursor-pointer transition-all hover:bg-[#2E7D52] hover:-translate-y-0.5 hover:shadow-lg" onclick="window.location.href='/dashboard'">
            Go to Dashboard
        </button>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    // Toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('passwordToggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    
    // Show toast messages
    document.addEventListener('DOMContentLoaded', function() {
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                showToast('error', '{{ $error }}');
            @endforeach
        @endif
        
        @if (session('error'))
            showToast('error', '{{ session('error') }}');
        @endif
        
        @if (session('success'))
            showToast('success', '{{ session('success') }}');
        @endif
    });
    
    function showToast(type, message) {
        console.log('Showing toast:', type, message); // Debug log
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
            z-index: 9999;
            position: relative;
        `;
        
        const icon = document.createElement('i');
        icon.className = `fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}`;
        
        const text = document.createElement('span');
        text.textContent = message;
        
        toast.appendChild(icon);
        toast.appendChild(text);
        
        const container = document.getElementById('toast-container');
        if (container) {
            container.appendChild(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    if (container.contains(toast)) {
                        container.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        } else {
            console.error('Toast container not found');
        }
    }
    
    // Add animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Show loading state on form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent page reload
        
        const button = document.getElementById('loginButton');
        const buttonText = document.getElementById('buttonText');
        const spinner = document.getElementById('buttonSpinner');
        
        button.disabled = true;
        buttonText.style.display = 'none';
        spinner.style.display = 'block';
        
        // Get form data
        const formData = new FormData(e.target);
        
        // Send AJAX request
        fetch('/', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            button.disabled = false;
            buttonText.style.display = 'block';
            spinner.style.display = 'none';
            
            if (data.success) {
                showToast('success', data.message || 'Login successful! Admin access granted.');
                // Update CSRF token for next requests
                if (data.csrf_token) {
                    document.querySelector('input[name="_token"]').value = data.csrf_token;
                }
            } else if (data.errors) {
                // Show validation errors
                Object.values(data.errors).forEach(error => {
                    showToast('error', error);
                });
            } else if (data.message) {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            button.disabled = false;
            buttonText.style.display = 'block';
            spinner.style.display = 'none';
            showToast('error', 'Login failed: ' + error.message);
        });
    });
    
    // Auto-hide success dialog after 3 seconds
    @if (session('login_success'))
        setTimeout(function() {
            const dialog = document.getElementById('successDialog');
            if (dialog) {
                dialog.style.display = 'none';
                window.location.href = '/dashboard';
            }
        }, 3000);
    @endif
    
    // Clear error messages after 5 seconds
    setTimeout(function() {
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(function(element) {
            element.style.display = 'none';
        });
    }, 5000);
    
    // Clear success messages after 3 seconds
    setTimeout(function() {
        const successMessages = document.querySelectorAll('.success-message');
        successMessages.forEach(function(element) {
            element.style.display = 'none';
        });
    }, 3000);
</script>
@endpush
