@props(['resources' => [], 'darkMode' => false, 'loading' => false, 'currentUser' => null, 'filterStats' => null])

<div class="space-y-6">
    <!-- Filter Stats -->
    @if($filterStats)
        <div class="flex items-center justify-between p-4 {{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200' }} border rounded-lg">
            <div class="flex items-center space-x-4 text-sm">
                <span class="{{ $darkMode ? 'text-gray-300' : 'text-gray-700' }}">
                    Showing {{ number_format($filterStats['final_count']) }} of {{ number_format($filterStats['original_count']) }} resources
                </span>
                @if($filterStats['total_excluded'] > 0)
                    <span class="text-orange-600 dark:text-orange-400">
                        ({{ number_format($filterStats['total_excluded']) }} filtered out)
                    </span>
                @endif
            </div>
            
            <div class="flex items-center space-x-2 text-xs">
                @foreach($filterStats['filters_applied'] as $filter)
                    @if($filter['excluded_count'] > 0)
                        <span class="px-2 py-1 rounded-full {{ $darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-600' }}">
                            {{ $filter['name'] }}: -{{ $filter['excluded_count'] }}
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
    
    @if($loading)
        <!-- Loading Skeleton -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            @for($i = 0; $i < 20; $i++)
                <x-compute-skeleton :dark-mode="$darkMode" />
            @endfor
        </div>
    @elseif(empty($resources))
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-12 space-y-4">
            <x-lucide-icon name="server" class="h-12 w-12 {{ $darkMode ? 'text-gray-600' : 'text-gray-400' }}" />
            <h3 class="text-lg font-medium {{ $darkMode ? 'text-gray-300' : 'text-gray-900' }}">
                No compute resources found
            </h3>
            <p class="text-sm {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }} max-w-md text-center">
                Try adjusting your filters or refresh the page to load resources.
            </p>
            <button 
                onclick="window.location.reload()"
                class="mt-4 px-4 py-2 text-sm font-medium rounded-lg {{ $darkMode ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition-colors">
                <x-lucide-icon name="refresh-cw" class="h-4 w-4 mr-2 inline" />
                Refresh
            </button>
        </div>
    @else
        <!-- Resource Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6"
             x-data="{ 
                 selectedResources: new Set(),
                 selectResource(id) {
                     if (this.selectedResources.has(id)) {
                         this.selectedResources.delete(id);
                     } else {
                         this.selectedResources.add(id);
                     }
                 },
                 isSelected(id) {
                     return this.selectedResources.has(id);
                 }
             }">
            
            @foreach($resources as $resource)
                <div class="relative"
                     :class="{ 'ring-2 ring-blue-500': isSelected('{{ $resource['id'] }}') }"
                     @click="selectResource('{{ $resource['id'] }}')">
                    <x-compute-card 
                        :resource="$resource" 
                        :dark-mode="$darkMode"
                        :current-user="$currentUser" />
                </div>
            @endforeach
        </div>
        
        <!-- Resource Summary -->
        <div class="mt-8 p-4 {{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200' }} border rounded-lg">
            <h3 class="font-medium text-sm {{ $darkMode ? 'text-gray-200' : 'text-gray-900' }} mb-3">
                Resource Summary
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                @php
                    $gpuCount = count(array_filter($resources, fn($r) => $r['resource_type'] === 'GPU'));
                    $cpuCount = count(array_filter($resources, fn($r) => $r['resource_type'] === 'CPU'));
                    $availableCount = count(array_filter($resources, function($r) use ($currentUser) {
                        $rentalStatus = $r['rental_status'] ?? null;
                        return !$rentalStatus || ($rentalStatus['status'] ?? '') !== 'active';
                    }));
                    $myRentalsCount = 0;
                    if ($currentUser) {
                        $myRentalsCount = count(array_filter($resources, function($r) use ($currentUser) {
                            $rentalStatus = $r['rental_status'] ?? null;
                            return $rentalStatus && 
                                   ($rentalStatus['status'] ?? '') === 'active' && 
                                   ($rentalStatus['user_id'] ?? '') === $currentUser['id'];
                        }));
                    }
                @endphp
                
                <div class="text-center p-3 {{ $darkMode ? 'bg-gray-700' : 'bg-white' }} rounded-lg">
                    <div class="text-2xl font-bold text-amber-500">{{ $gpuCount }}</div>
                    <div class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">GPU Resources</div>
                </div>
                
                <div class="text-center p-3 {{ $darkMode ? 'bg-gray-700' : 'bg-white' }} rounded-lg">
                    <div class="text-2xl font-bold text-blue-500">{{ $cpuCount }}</div>
                    <div class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">CPU Resources</div>
                </div>
                
                <div class="text-center p-3 {{ $darkMode ? 'bg-gray-700' : 'bg-white' }} rounded-lg">
                    <div class="text-2xl font-bold text-green-500">{{ $availableCount }}</div>
                    <div class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">Available</div>
                </div>
                
                @if($currentUser)
                    <div class="text-center p-3 {{ $darkMode ? 'bg-gray-700' : 'bg-white' }} rounded-lg">
                        <div class="text-2xl font-bold text-purple-500">{{ $myRentalsCount }}</div>
                        <div class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">My Active</div>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Resource Actions -->
        <div class="flex items-center justify-between pt-4 border-t {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
            <div class="flex items-center space-x-4">
                <button 
                    onclick="refreshResources()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg {{ $darkMode ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition-colors">
                    <x-lucide-icon name="refresh-cw" class="h-4 w-4 mr-2" />
                    Refresh
                </button>
                
                <button 
                    onclick="exportResources()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg {{ $darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-500 hover:text-gray-700' }} transition-colors">
                    <x-lucide-icon name="download" class="h-4 w-4 mr-2" />
                    Export
                </button>
            </div>
            
            <div class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                Last updated: <span id="last-updated">{{ now()->format('H:i:s') }}</span>
            </div>
        </div>
    @endif
</div>

<script>
function refreshResources() {
    // Show loading state
    const gridContainer = document.querySelector('[x-data]');
    
    // Dispatch refresh event
    window.dispatchEvent(new CustomEvent('refresh-resources'));
    
    // Update timestamp
    document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
}

function exportResources() {
    const resources = @json($resources);
    const dataStr = JSON.stringify(resources, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `polaris-compute-resources-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
}

// Auto-refresh every 15 seconds
setInterval(() => {
    refreshResources();
}, 15000);

// Listen for deployment events
window.addEventListener('deploy-resource', function(event) {
    const resourceId = event.detail.resourceId;
    console.log('Deploy resource:', resourceId);
    // TODO: Implement deployment modal/process
});

window.addEventListener('show-rental-details', function(event) {
    const resourceId = event.detail.resourceId;
    console.log('Show rental details:', resourceId);
    // TODO: Implement rental details modal
});
</script>