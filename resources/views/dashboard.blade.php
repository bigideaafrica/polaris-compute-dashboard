@extends('layouts.app')

@section('title', $pageTitle ?? 'Polaris Compute Resources')

@section('content')
<div class="min-h-screen {{ $darkMode ? 'bg-gray-900' : 'bg-gray-50' }}" x-data="computeDashboard()">
    <!-- Header -->
    <header class="{{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200' }} border-b sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <x-lucide-icon name="server" class="h-8 w-8 text-blue-600" />
                        <h1 class="text-xl font-bold {{ $darkMode ? 'text-white' : 'text-gray-900' }}">
                            Polaris Compute
                        </h1>
                    </div>
                    
                    @if($resourceCount > 0)
                        <div class="hidden sm:flex items-center space-x-2 text-sm {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                            <span>{{ number_format($resourceCount) }} resources</span>
                            @if($filterStats && $filterStats['total_excluded'] > 0)
                                <span class="text-orange-600 dark:text-orange-400">
                                    ({{ number_format($filterStats['total_excluded']) }} filtered)
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                
                <!-- Header Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="relative hidden md:block">
                        <x-lucide-icon name="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}" />
                        <input 
                            type="text" 
                            placeholder="Search resources..."
                            x-model="searchQuery"
                            class="pl-10 pr-4 py-2 w-64 text-sm border rounded-lg {{ $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200 placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500' }} focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Refresh Button -->
                    <button 
                        @click="refreshResources()"
                        :disabled="isLoading"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ $darkMode ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition-colors disabled:opacity-50">
                        <x-lucide-icon name="refresh-cw" class="h-4 w-4 mr-2" :class="{ 'animate-spin': isLoading }" />
                        <span x-text="isLoading ? 'Loading...' : 'Refresh'"></span>
                    </button>
                    
                    <!-- Dark Mode Toggle -->
                    <button 
                        onclick="toggleDarkMode()"
                        class="p-2 rounded-lg {{ $darkMode ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition-colors">
                        @if($darkMode)
                            <x-lucide-icon name="sun" class="h-4 w-4" />
                        @else
                            <x-lucide-icon name="moon" class="h-4 w-4" />
                        @endif
                    </button>
                    
                    <!-- Filters Toggle (Mobile) -->
                    <button 
                        @click="showFilters = !showFilters"
                        class="md:hidden p-2 rounded-lg {{ $darkMode ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition-colors">
                        <x-lucide-icon name="filter" class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar with Filters -->
            <aside class="lg:w-80 space-y-6" 
                   :class="{ 'hidden': !showFilters }"
                   class="lg:block">
                
                <!-- Filter Panel -->
                <div class="{{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200' }} border rounded-lg p-6">
                    <x-filter-panel 
                        :filters="$filterSettings"
                        :active-filters="$activeFilters"
                        :filter-stats="$filterStats"
                        :dark-mode="$darkMode" />
                </div>
                
                <!-- API Status -->
                @if(isset($apiStats) && !empty($apiStats))
                    <div class="{{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200' }} border rounded-lg p-6">
                        <h3 class="font-medium text-sm {{ $darkMode ? 'text-gray-200' : 'text-gray-900' }} mb-3">
                            API Status
                        </h3>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">Total Resources:</span>
                                <span class="{{ $darkMode ? 'text-gray-200' : 'text-gray-700' }}">{{ number_format($apiStats['total_resources'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">GPU Resources:</span>
                                <span class="text-amber-600">{{ number_format($apiStats['gpu_resources'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">CPU Resources:</span>
                                <span class="text-blue-600">{{ number_format($apiStats['cpu_resources'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">Verified:</span>
                                <span class="text-green-600">{{ number_format($apiStats['verified_resources'] ?? 0) }}</span>
                            </div>
                            @if(isset($apiStats['fake_gpu_resources']) && $apiStats['fake_gpu_resources'] > 0)
                                <div class="flex justify-between">
                                    <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">Fake GPUs:</span>
                                    <span class="text-red-600">{{ number_format($apiStats['fake_gpu_resources']) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </aside>
            
            <!-- Main Content Area -->
            <main class="flex-1 space-y-6">
                <!-- Error Message -->
                @if(isset($error))
                    <div class="p-4 border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 rounded-lg">
                        <div class="flex items-center">
                            <x-lucide-icon name="alert-triangle" class="h-5 w-5 text-red-600 dark:text-red-400 mr-2" />
                            <p class="text-red-700 dark:text-red-300">{{ $error }}</p>
                        </div>
                    </div>
                @endif
                
                <!-- Quick Actions -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Quick Type Filters -->
                        @php
                            $currentType = $activeFilters['type'] ?? 'all';
                        @endphp
                        <div class="flex items-center space-x-1">
                            <a href="{{ request()->fullUrlWithQuery(['type' => 'all']) }}"
                               class="px-3 py-1 text-xs font-medium rounded-full transition-colors {{ $currentType === 'all' ? ($darkMode ? 'bg-blue-600 text-white' : 'bg-blue-600 text-white') : ($darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200') }}">
                                All
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['type' => 'gpu']) }}"
                               class="px-3 py-1 text-xs font-medium rounded-full transition-colors {{ $currentType === 'gpu' ? ($darkMode ? 'bg-amber-600 text-white' : 'bg-amber-600 text-white') : ($darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200') }}">
                                GPU
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['type' => 'cpu']) }}"
                               class="px-3 py-1 text-xs font-medium rounded-full transition-colors {{ $currentType === 'cpu' ? ($darkMode ? 'bg-blue-600 text-white' : 'bg-blue-600 text-white') : ($darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200') }}">
                                CPU
                            </a>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <button 
                            @click="exportResources()"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg {{ $darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-500 hover:text-gray-700' }} transition-colors">
                            <x-lucide-icon name="download" class="h-4 w-4 mr-1" />
                            Export
                        </button>
                        
                        <span class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                            Last updated: <span x-text="lastUpdated"></span>
                        </span>
                    </div>
                </div>
                
                <!-- Resources Grid -->
                <x-compute-grid 
                    :resources="$resources"
                    :loading="false"
                    :dark-mode="$darkMode"
                    :current-user="$currentUser"
                    :filter-stats="$filterStats" />
            </main>
        </div>
    </div>
</div>

<script>
function computeDashboard() {
    return {
        isLoading: false,
        showFilters: window.innerWidth >= 1024, // Show on desktop by default
        searchQuery: '',
        lastUpdated: new Date().toLocaleTimeString(),
        
        async refreshResources() {
            this.isLoading = true;
            
            try {
                const response = await fetch('/api/compute/refresh', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.lastUpdated = new Date().toLocaleTimeString();
                    // Reload the page to show updated data
                    window.location.reload();
                } else {
                    console.error('Refresh failed:', data.error);
                }
            } catch (error) {
                console.error('Refresh error:', error);
            } finally {
                this.isLoading = false;
            }
        },
        
        exportResources() {
            const params = new URLSearchParams(window.location.search);
            params.set('format', 'json');
            
            fetch(`/api/compute/export?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const blob = new Blob([JSON.stringify(data.data, null, 2)], {
                            type: 'application/json'
                        });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = data.filename;
                        a.click();
                        URL.revokeObjectURL(url);
                    }
                })
                .catch(error => {
                    console.error('Export error:', error);
                });
        }
    }
}

function toggleDarkMode() {
    const url = new URL(window.location);
    const currentDark = url.searchParams.get('dark') === 'true';
    url.searchParams.set('dark', (!currentDark).toString());
    window.location.href = url.toString();
}

// Auto-refresh every 15 seconds
setInterval(() => {
    // Only auto-refresh if user is not actively filtering
    if (document.visibilityState === 'visible') {
        const dashboard = Alpine.$data(document.querySelector('[x-data]'));
        if (dashboard && !dashboard.isLoading) {
            dashboard.refreshResources();
        }
    }
}, 15000);

// Handle resize for filter panel
window.addEventListener('resize', () => {
    const dashboard = Alpine.$data(document.querySelector('[x-data]'));
    if (dashboard && window.innerWidth >= 1024) {
        dashboard.showFilters = true;
    }
});
</script>
@endsection