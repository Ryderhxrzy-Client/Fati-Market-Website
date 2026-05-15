<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Fati Market</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --primary-green: #1A5C38;
            --light-green: #2E7D52;
            --gold: #D4A017;
            --dark-text: #1C1B1F;
            --muted-text: #6B6B6B;
            --light-bg: #F5F5F5;
            --border-color: #EEEEE8;
        }

        * {
            --tw-ring-offset-color: transparent;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
        }

        .drawer-container {
            transition: transform 0.3s ease;
        }

        .drawer-container.open {
            transform: translateX(0);
        }

        @media (max-width: 768px) {
            .drawer-container {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                width: 280px;
                transform: translateX(-100%);
                z-index: 40;
            }
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin: 4px 0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--muted-text);
            text-decoration: none;
            font-size: 14px;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background-color: rgba(26, 92, 56, 0.1);
            color: var(--primary-green);
        }

        .sidebar-link i {
            width: 20px;
            text-align: center;
        }

        .admin-badge {
            display: inline-block;
            background-color: var(--gold);
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-green);
            margin: 12px 0;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(26, 92, 56, 0.2);
            border-top-color: var(--primary-green);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 16px 20px;
            border-radius: 8px;
            color: white;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
        }

        .toast.success {
            background-color: #10b981;
        }

        .toast.error {
            background-color: #ef4444;
        }

        .toast.info {
            background-color: var(--primary-green);
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar/Drawer -->
        <div class="drawer-container bg-white shadow-lg w-64 overflow-y-auto">
            <!-- Logo Section -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">Fati Market</h1>
                        <p class="text-xs text-gray-500">Admin Panel</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gray-300"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ session('admin_data.name') ?? 'Admin' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ session('admin_data.email') ?? 'admin@fatimarket.com' }}</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="p-4">
                <!-- Dashboard -->
                <div class="mb-6">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link active" data-page="dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- Inventory Management Dropdown -->
                <div class="mb-6">
                    <button class="sidebar-link w-full text-left flex justify-between items-center" onclick="toggleMenu(this)">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-box"></i>
                            <span>Inventory Management</span>
                        </span>
                        <i class="fas fa-chevron-down text-xs transition-transform"></i>
                    </button>
                    <div class="submenu hidden pl-8 space-y-1 mt-2">
                        <a href="{{ route('admin.private-offers') }}" class="sidebar-link text-sm" data-page="private-offers">
                            <i class="fas fa-lock text-xs"></i>
                            <span>Private Offers</span>
                        </a>
                        <a href="{{ route('admin.acquired-items') }}" class="sidebar-link text-sm" data-page="acquired-items">
                            <i class="fas fa-shopping-bag text-xs"></i>
                            <span>Acquired Items</span>
                        </a>
                        <a href="{{ route('admin.public-listings') }}" class="sidebar-link text-sm" data-page="public-listings">
                            <i class="fas fa-globe text-xs"></i>
                            <span>Public Listings</span>
                        </a>
                        <a href="{{ route('admin.reserved-items') }}" class="sidebar-link text-sm" data-page="reserved-items">
                            <i class="fas fa-clock text-xs"></i>
                            <span>Reserved Items</span>
                        </a>
                        <a href="{{ route('admin.sold-items') }}" class="sidebar-link text-sm" data-page="sold-items">
                            <i class="fas fa-check-circle text-xs"></i>
                            <span>Sold Items</span>
                        </a>
                    </div>
                </div>

                <!-- Transactions Dropdown -->
                <div class="mb-6">
                    <button class="sidebar-link w-full text-left flex justify-between items-center" onclick="toggleMenu(this)">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-exchange-alt"></i>
                            <span>Transactions</span>
                        </span>
                        <i class="fas fa-chevron-down text-xs transition-transform"></i>
                    </button>
                    <div class="submenu hidden pl-8 space-y-1 mt-2">
                        <a href="{{ route('admin.transactions') }}" class="sidebar-link text-sm" data-page="transactions">
                            <i class="fas fa-list text-xs"></i>
                            <span>All Transactions</span>
                        </a>
                        <a href="{{ route('admin.transactions') }}?filter=completed" class="sidebar-link text-sm" data-page="transactions-completed">
                            <i class="fas fa-check text-xs"></i>
                            <span>Completed</span>
                        </a>
                        <a href="{{ route('admin.transactions') }}?filter=pending" class="sidebar-link text-sm" data-page="transactions-pending">
                            <i class="fas fa-hourglass text-xs"></i>
                            <span>Pending</span>
                        </a>
                        <a href="{{ route('admin.transactions') }}?filter=failed" class="sidebar-link text-sm" data-page="transactions-failed">
                            <i class="fas fa-times text-xs"></i>
                            <span>Failed</span>
                        </a>
                    </div>
                </div>

                <!-- Report & Analytics -->
                <div class="mb-6">
                    <a href="{{ route('admin.reports') }}" class="sidebar-link" data-page="reports">
                        <i class="fas fa-chart-bar"></i>
                        <span>Report & Analytics</span>
                    </a>
                </div>

                <!-- Categories -->
                <div class="mb-6">
                    <a href="{{ route('admin.categories') }}" class="sidebar-link" data-page="categories">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </div>

                <!-- Activity Logs -->
                <div class="mb-6">
                    <a href="{{ route('admin.activity') }}" class="sidebar-link" data-page="activity">
                        <i class="fas fa-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </div>

                <!-- Chat -->
                <div class="mb-6">
                    <a href="{{ route('admin.conversations') }}" class="sidebar-link" data-page="conversations">
                        <i class="fas fa-comments"></i>
                        <span>Chat</span>
                        <span class="ml-auto text-xs bg-red-500 text-white px-2 py-1 rounded" id="unreadCount" style="display: none;">0</span>
                    </a>
                </div>

                <!-- User Management -->
                <div class="mb-6">
                    <a href="{{ route('admin.students') }}" class="sidebar-link" data-page="students">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                </div>

                <!-- Profile -->
                <div class="mb-6">
                    <a href="{{ route('admin.profile') }}" class="sidebar-link" data-page="profile">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                </div>

                <!-- Settings -->
                <div class="mb-6">
                    <a href="{{ route('admin.settings') }}" class="sidebar-link" data-page="settings">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="sidebar-link w-full text-left text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-4">
                    <button class="md:hidden" onclick="toggleDrawer()">
                        <i class="fas fa-bars text-2xl text-gray-700"></i>
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900">@yield('title', 'Dashboard')</h2>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <input type="search" placeholder="Search..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <button class="relative">
                        <i class="fas fa-bell text-xl text-gray-600"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="w-10 h-10 rounded-full bg-gray-300"></div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script>
        function toggleDrawer() {
            const drawer = document.querySelector('.drawer-container');
            drawer.classList.toggle('open');
        }

        function toggleMenu(button) {
            const submenu = button.nextElementSibling;
            const icon = button.querySelector('.fa-chevron-down');
            submenu.classList.toggle('hidden');
            icon.style.transform = submenu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Update active link based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });

            // Keep inventory menu open if a submenu item is active
            const activeSubmenuLink = document.querySelector('.inventory-submenu .sidebar-link.active');
            if (activeSubmenuLink) {
                const submenu = activeSubmenuLink.closest('.inventory-submenu');
                submenu.classList.remove('hidden');
                const icon = submenu.previousElementSibling.querySelector('.fa-chevron-down');
                if (icon) icon.style.transform = 'rotate(180deg)';
            }
        });

        // Close drawer on mobile when link is clicked
        if (window.innerWidth < 768) {
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.addEventListener('click', () => {
                    document.querySelector('.drawer-container').classList.remove('open');
                });
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
