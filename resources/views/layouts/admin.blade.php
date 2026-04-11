<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Fati Market</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-sidebar-header">
                <h2>Fati Market</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" 
                   class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-dashboard"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.products.index') }}" 
                   class="admin-nav-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <i class="fas fa-box"></i>
                    Products
                </a>
                <a href="{{ route('admin.orders.index') }}" 
                   class="admin-nav-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i>
                    Orders
                </a>
                <a href="{{ route('admin.customers.index') }}" 
                   class="admin-nav-item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    Customers
                </a>
                <a href="{{ route('admin.categories.index') }}" 
                   class="admin-nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    Categories
                </a>
                <a href="{{ route('admin.reports.index') }}" 
                   class="admin-nav-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
                <a href="{{ route('admin.settings.index') }}" 
                   class="admin-nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main-content">
            <!-- Header -->
            <header class="admin-header">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>@yield('header-title', 'Admin Dashboard')</h1>
                </div>
                <div class="admin-header-actions">
                    @yield('header-actions')
                    <div class="user-info" style="display: flex; align-items: center; gap: 12px;">
                        <span style="color: var(--muted-text); font-size: 14px;">
                            {{ session('admin_data.first_name') }} {{ session('admin_data.last_name') ?? 'Admin User' }}
                        </span>
                        <div style="width: 40px; height: 40px; border-radius: 50%; background-color: var(--dark-green); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                            {{ strtoupper(substr(session('admin_data.first_name') ?? 'A', 0, 1)) }}
                        </div>
                        <form action="/logout" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm" onclick="return confirm('Are you sure you want to logout?')">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="admin-content">
                @yield('content')
            </div>
        </main>
    </div>
    
    @stack('scripts')
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            sidebar.classList.toggle('open');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target) &&
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>
