<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ request()->boolean('dark') ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Polaris Compute'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iOCIgZmlsbD0iIzI1NjNlYiIvPgo8cGF0aCBkPSJNOCAxMmg0djRIOHYtNHptNiAwaDR2NGgtNHYtNHptNiAwaDR2NGgtNHYtNHpNOCAxOGg0djRIOHYtNHptNiAwaDR2NGgtNHYtNHptNiAwaDR2NGgtNHYtNHoiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo=">

    <!-- Meta Tags -->
    <meta name="description" content="Polaris Compute Resources Dashboard - Browse and deploy GPU and CPU compute resources with advanced filtering">
    <meta name="keywords" content="compute, GPU, CPU, resources, cloud, deployment, polaris">
    <meta name="author" content="Polaris Compute">
    
    <!-- Open Graph -->
    <meta property="og:title" content="@yield('title', 'Polaris Compute Resources')">
    <meta property="og:description" content="Browse and deploy compute resources with advanced filtering">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Additional Head Content -->
    @stack('head')
</head>
<body class="font-sans antialiased {{ request()->boolean('dark') ? 'dark bg-gray-900 text-gray-100' : 'bg-gray-50 text-gray-900' }}">
    <!-- Page Content -->
    <div id="app">
        @yield('content')
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <!-- Global Loading Overlay -->
    <div id="global-loading" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700 dark:text-gray-300">Loading...</span>
        </div>
    </div>
    
    <!-- Global Scripts -->
    <script>
        // Global utility functions
        window.showLoading = function() {
            document.getElementById('global-loading').classList.remove('hidden');
        }
        
        window.hideLoading = function() {
            document.getElementById('global-loading').classList.add('hidden');
        }
        
        window.showToast = function(message, type = 'info', duration = 3000) {
            const toast = document.createElement('div');
            const bgColor = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'warning': 'bg-yellow-500',
                'info': 'bg-blue-500'
            }[type] || 'bg-blue-500';
            
            toast.className = `${bgColor} text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 transform transition-all duration-300 translate-x-full`;
            toast.innerHTML = `
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            `;
            
            document.getElementById('toast-container').appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 10);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.remove();
                    }
                }, 300);
            }, duration);
        }
        
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
            showToast('An unexpected error occurred', 'error');
        });
        
        // Global unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);
            showToast('A network error occurred', 'error');
        });
        
        // CSRF token setup for AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        if (token) {
            window.axios = {
                defaults: {
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                }
            };
        }
        
        // Global keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                window.location.reload();
            }
            
            // Ctrl/Cmd + K for search (if search input exists)
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                const searchInput = document.querySelector('input[type="text"][placeholder*="Search"]');
                if (searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }
            }
            
            // F for filters toggle
            if (e.key === 'f' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                const activeElement = document.activeElement;
                if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
                    const dashboard = Alpine.$data(document.querySelector('[x-data]'));
                    if (dashboard && 'showFilters' in dashboard) {
                        dashboard.showFilters = !dashboard.showFilters;
                    }
                }
            }
        });
        
        // Page visibility API for auto-refresh
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // Page became visible, refresh data if it's been more than 30 seconds
                const lastRefresh = localStorage.getItem('lastRefresh');
                const now = Date.now();
                if (!lastRefresh || (now - parseInt(lastRefresh)) > 30000) {
                    const dashboard = Alpine.$data(document.querySelector('[x-data]'));
                    if (dashboard && typeof dashboard.refreshResources === 'function') {
                        dashboard.refreshResources();
                    }
                }
            }
        });
        
        // Store refresh timestamp
        function updateLastRefresh() {
            localStorage.setItem('lastRefresh', Date.now().toString());
        }
        
        // Call on page load
        updateLastRefresh();
    </script>
    
    <!-- Additional Body Scripts -->
    @stack('scripts')
</body>
</html>