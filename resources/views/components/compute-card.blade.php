@props(['resource', 'darkMode' => false, 'showDeployButton' => true, 'currentUser' => null])

@php
    $isGpu = $resource['resource_type'] === 'GPU';
    $specs = $isGpu ? ($resource['gpu_specs'] ?? []) : ($resource['cpu_specs'] ?? []);
    $iconColor = $isGpu ? 'text-amber-500' : 'text-blue-500';
    
    // Resource details
    $name = $specs['gpu_name'] ?? $specs['model_name'] ?? $specs['cpu_name'] ?? ($isGpu ? 'GPU' : 'CPU');
    $memory = $isGpu ? ($specs['memory'] ?? $specs['memory_size'] ?? 'N/A') : ($resource['ram'] ?? 'N/A');
    $cores = $isGpu ? ($specs['cuda_cores'] ?? 'N/A') : ($specs['total_cores'] ?? $specs['cores_per_cpu'] ?? 'N/A');
    $clockSpeed = $specs['clock_speed'] ?? 'N/A';
    
    // Storage
    $storage = $resource['storage'] ?? [];
    $storageSize = $storage['total_gb'] ?? 'N/A';
    $storageType = $storage['type'] ?? '';
    $storageDisplay = $storageSize . 'GB ' . $storageType;
    
    // Status
    $isActive = $resource['is_active'] ?? false;
    $isVerified = ($resource['validation_status'] ?? '') === 'verified';
    $hourlyPrice = $resource['hourly_price'] ?? 0;
    
    // Rental status
    $rentalStatus = $resource['rental_status'] ?? null;
    $isRented = $rentalStatus && ($rentalStatus['status'] ?? '') === 'active';
    $isMyRental = $currentUser && $isRented && ($rentalStatus['user_id'] ?? '') === $currentUser['id'];
    
    // Monitoring health
    $monitoring = $resource['monitoring_status'] ?? [];
    $authOk = ($monitoring['auth']['status'] ?? '') === 'ok';
    $connOk = ($monitoring['conn']['status'] ?? '') === 'ok';
    $dockerOk = ($monitoring['docker']['running'] ?? false) && ($monitoring['docker']['user_group'] ?? false);
    $isHealthy = $authOk && $connOk && $dockerOk;
    
    // Card states
    $isDeployable = $isActive && $isVerified && $isHealthy && !$isRented;
    $cardClasses = 'relative bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-all duration-200';
    
    if ($darkMode) {
        $cardClasses = 'relative bg-gray-800 border border-gray-700 rounded-lg shadow-sm hover:shadow-md transition-all duration-200';
    }
    
    if (!$isDeployable) {
        $cardClasses .= ' opacity-75';
    }
@endphp

<div class="{{ $cardClasses }}" 
     data-resource-id="{{ $resource['id'] }}"
     data-resource-type="{{ strtolower($resource['resource_type']) }}"
     x-data="{ showDetails: false }">
     
    <!-- Header -->
    <div class="p-4 border-b {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
        <div class="flex items-start justify-between">
            <div class="flex items-center space-x-2">
                <!-- Resource Type Icon -->
                @if($isGpu)
                    <x-lucide-icon name="monitor" class="h-5 w-5 {{ $iconColor }}" />
                @else
                    <x-lucide-icon name="cpu" class="h-5 w-5 {{ $iconColor }}" />
                @endif
                
                <!-- Resource Name -->
                <h3 class="font-medium text-sm {{ $darkMode ? 'text-gray-200' : 'text-gray-900' }} truncate">
                    {{ $name }}
                </h3>
            </div>
            
            <!-- Status Badges -->
            <div class="flex items-center space-x-1">
                @if($isVerified)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                        <x-lucide-icon name="shield-check" class="h-3 w-3 mr-1" />
                        Verified
                    </span>
                @endif
                
                @if(!$isHealthy)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                        <x-lucide-icon name="alert-triangle" class="h-3 w-3 mr-1" />
                        Issues
                    </span>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Specifications -->
    <div class="p-4 space-y-3">
        <!-- Memory/RAM -->
        <div class="flex justify-between items-center">
            <span class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                {{ $isGpu ? 'Memory' : 'RAM' }}
            </span>
            <span class="text-xs font-mono {{ $darkMode ? 'text-gray-200' : 'text-gray-700' }}">
                {{ $memory }}
            </span>
        </div>
        
        <!-- Cores -->
        <div class="flex justify-between items-center">
            <span class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                {{ $isGpu ? 'CUDA Cores' : 'CPU Cores' }}
            </span>
            <span class="text-xs font-mono {{ $darkMode ? 'text-gray-200' : 'text-gray-700' }}">
                {{ number_format($cores) }}
            </span>
        </div>
        
        <!-- Clock Speed -->
        <div class="flex justify-between items-center">
            <span class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                Clock Speed
            </span>
            <span class="text-xs font-mono {{ $darkMode ? 'text-gray-200' : 'text-gray-700' }}">
                {{ $clockSpeed }}
            </span>
        </div>
        
        <!-- Storage -->
        <div class="flex justify-between items-center">
            <span class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                Storage
            </span>
            <span class="text-xs font-mono {{ $darkMode ? 'text-gray-200' : 'text-gray-700' }}">
                {{ $storageDisplay }}
            </span>
        </div>
        
        <!-- Location -->
        @if($resource['location'] !== 'Unknown Location')
            <div class="flex justify-between items-center">
                <span class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                    Location
                </span>
                <span class="text-xs {{ $darkMode ? 'text-gray-200' : 'text-gray-700' }}">
                    {{ $resource['location'] }}
                </span>
            </div>
        @endif
    </div>
    
    <!-- Pricing -->
    <div class="px-4 py-3 {{ $darkMode ? 'bg-gray-750' : 'bg-gray-50' }} border-t {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
        <div class="flex items-center justify-between">
            <span class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                Hourly Rate
            </span>
            <span class="text-sm font-bold {{ $darkMode ? 'text-green-400' : 'text-green-600' }}">
                ${{ number_format($hourlyPrice, 2) }}/hr
            </span>
        </div>
    </div>
    
    <!-- Action Button -->
    @if($showDeployButton)
        <div class="p-4 border-t {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
            @if($isRented)
                @if($isMyRental)
                    <!-- My Active Rental -->
                    <button 
                        class="w-full px-4 py-2 text-sm font-medium rounded-lg border-2 border-blue-500 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-400 dark:hover:bg-blue-900/20 transition-colors"
                        onclick="showRentalDetails('{{ $resource['id'] }}')">
                        <x-lucide-icon name="terminal" class="h-4 w-4 mr-2 inline" />
                        Manage Instance
                    </button>
                @else
                    <!-- Rented by Others -->
                    <button 
                        disabled
                        class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500">
                        <x-lucide-icon name="user" class="h-4 w-4 mr-2 inline" />
                        In Use
                    </button>
                @endif
            @elseif($isDeployable)
                <!-- Available for Deployment -->
                <button 
                    class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors"
                    onclick="deployResource('{{ $resource['id'] }}')">
                    <x-lucide-icon name="play" class="h-4 w-4 mr-2 inline" />
                    Deploy
                </button>
            @else
                <!-- Not Available -->
                <button 
                    disabled
                    class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500">
                    <x-lucide-icon name="x-circle" class="h-4 w-4 mr-2 inline" />
                    Unavailable
                </button>
            @endif
        </div>
    @endif
    
    <!-- Rental Status Overlay -->
    @if($isRented)
        <div class="absolute bottom-0 left-0 right-0 z-10">
            <div class="px-3 py-2 flex items-center {{ $isMyRental ? 'bg-green-600' : 'bg-blue-600' }} text-white text-xs">
                <span class="font-bold">IN USE</span>
                <div class="flex-1 flex justify-center">
                    <div class="h-4 w-px bg-white/60"></div>
                </div>
                <div class="flex items-center gap-1">
                    <x-lucide-icon name="clock" class="h-3 w-3" />
                    @if($isMyRental && isset($rentalStatus['rental_end_date']))
                        <span class="font-mono" x-data="countdown('{{ $rentalStatus['rental_end_date'] }}')" x-text="timeRemaining"></span>
                    @else
                        <span class="font-mono">Active</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
    
    <!-- Details Toggle -->
    <div class="absolute top-2 right-2">
        <button 
            @click="showDetails = !showDetails"
            class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <x-lucide-icon name="more-vertical" class="h-4 w-4 {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}" />
        </button>
    </div>
    
    <!-- Expandable Details -->
    <div x-show="showDetails" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="absolute top-full left-0 right-0 mt-2 p-4 {{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200' }} border rounded-lg shadow-lg z-20">
        
        <h4 class="font-medium text-sm {{ $darkMode ? 'text-gray-200' : 'text-gray-900' }} mb-2">
            Resource Details
        </h4>
        
        <!-- Resource ID -->
        <div class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }} mb-2">
            ID: <span class="font-mono">{{ $resource['id'] }}</span>
        </div>
        
        <!-- Monitoring Status -->
        <div class="space-y-1 text-xs">
            <div class="flex justify-between">
                <span>Authentication:</span>
                <span class="{{ $authOk ? 'text-green-600' : 'text-red-600' }}">
                    {{ $authOk ? 'OK' : 'Failed' }}
                </span>
            </div>
            <div class="flex justify-between">
                <span>Connection:</span>
                <span class="{{ $connOk ? 'text-green-600' : 'text-red-600' }}">
                    {{ $connOk ? 'OK' : 'Failed' }}
                </span>
            </div>
            <div class="flex justify-between">
                <span>Docker:</span>
                <span class="{{ $dockerOk ? 'text-green-600' : 'text-red-600' }}">
                    {{ $dockerOk ? 'Ready' : 'Issues' }}
                </span>
            </div>
        </div>
        
        @if($rentalStatus && $isMyRental)
            <!-- Connection Info -->
            <div class="mt-3 pt-3 border-t {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
                <h5 class="font-medium text-xs {{ $darkMode ? 'text-gray-200' : 'text-gray-900' }} mb-2">
                    Connection Details
                </h5>
                @php
                    $containerInfo = $rentalStatus['container_info'] ?? [];
                @endphp
                @if($containerInfo)
                    <div class="space-y-1 text-xs font-mono">
                        <div>Host: {{ $containerInfo['host'] ?? 'N/A' }}</div>
                        <div>Port: {{ $containerInfo['ssh_port'] ?? 'N/A' }}</div>
                        <div>User: {{ $containerInfo['username'] ?? 'N/A' }}</div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<script>
function deployResource(resourceId) {
    // Trigger deployment modal or process
    window.dispatchEvent(new CustomEvent('deploy-resource', {
        detail: { resourceId: resourceId }
    }));
}

function showRentalDetails(resourceId) {
    // Show rental management modal
    window.dispatchEvent(new CustomEvent('show-rental-details', {
        detail: { resourceId: resourceId }
    }));
}

// Countdown timer for rental expiration
function countdown(endDate) {
    return {
        timeRemaining: '00:00:00',
        init() {
            this.updateCountdown();
            setInterval(() => {
                this.updateCountdown();
            }, 1000);
        },
        updateCountdown() {
            const now = new Date().getTime();
            const end = new Date(endDate).getTime();
            const distance = end - now;
            
            if (distance < 0) {
                this.timeRemaining = '00:00:00';
                return;
            }
            
            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            this.timeRemaining = String(hours).padStart(2, '0') + ':' + 
                               String(minutes).padStart(2, '0') + ':' + 
                               String(seconds).padStart(2, '0');
        }
    }
}
</script>