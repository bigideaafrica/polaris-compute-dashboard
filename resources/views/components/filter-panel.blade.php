@props(['filters' => [], 'activeFilters' => [], 'filterStats' => null, 'darkMode' => false])

<div class="space-y-6">
    <!-- Filter Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold {{ $darkMode ? 'text-gray-200' : 'text-gray-900' }}">
            Filters
        </h2>
        <button 
            onclick="resetAllFilters()"
            class="text-sm {{ $darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-500 hover:text-gray-700' }} transition-colors">
            Reset All
        </button>
    </div>
    
    <!-- Quick Filters -->
    <div class="space-y-4">
        <!-- Resource Type Filter -->
        <div class="space-y-2">
            <label class="block text-sm font-medium {{ $darkMode ? 'text-gray-300' : 'text-gray-700' }}">
                Resource Type
            </label>
            <div class="flex space-x-2" x-data="{ selected: '{{ $activeFilters['type'] ?? 'all' }}' }">
                @foreach(['all' => 'All Resources', 'gpu' => 'GPU Only', 'cpu' => 'CPU Only'] as $value => $label)
                    <button 
                        @click="selected = '{{ $value }}'; updateFilter('type', '{{ $value }}')"
                        :class="{
                            '{{ $darkMode ? 'bg-blue-600 text-white' : 'bg-blue-600 text-white' }}': selected === '{{ $value }}',
                            '{{ $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}': selected !== '{{ $value }}'
                        }"
                        class="px-3 py-2 text-xs font-medium rounded-lg transition-colors">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
        
        <!-- Ownership Filter -->
        <div class="space-y-2">
            <label class="block text-sm font-medium {{ $darkMode ? 'text-gray-300' : 'text-gray-700' }}">
                Ownership
            </label>
            <div class="flex space-x-2" x-data="{ selected: '{{ $activeFilters['ownership'] ?? 'all' }}' }">
                @foreach(['all' => 'All Resources', 'mine' => 'My Resources'] as $value => $label)
                    <button 
                        @click="selected = '{{ $value }}'; updateFilter('ownership', '{{ $value }}')"
                        :class="{
                            '{{ $darkMode ? 'bg-purple-600 text-white' : 'bg-purple-600 text-white' }}': selected === '{{ $value }}',
                            '{{ $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}': selected !== '{{ $value }}'
                        }"
                        class="px-3 py-2 text-xs font-medium rounded-lg transition-colors">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- System Filters -->
    <div class="space-y-4">
        <h3 class="text-sm font-medium {{ $darkMode ? 'text-gray-300' : 'text-gray-700' }}">
            System Filters
        </h3>
        
        @foreach($filters as $filter)
            <div class="flex items-center justify-between p-3 {{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200' }} border rounded-lg">
                <div class="flex-1">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input 
                            type="checkbox" 
                            {{ $filter['enabled'] ? 'checked' : '' }}
                            onchange="toggleSystemFilter('{{ $filter['name'] }}')"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <div>
                            <div class="text-sm font-medium {{ $darkMode ? 'text-gray-200' : 'text-gray-900' }}">
                                {{ ucfirst(str_replace('_', ' ', $filter['name'])) }}
                            </div>
                            <div class="text-xs {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">
                                {{ $filter['description'] }}
                            </div>
                        </div>
                    </label>
                </div>
                
                <!-- Filter Impact Badge -->
                @if($filterStats && isset($filterStats['filters_applied']))
                    @php
                        $filterStat = collect($filterStats['filters_applied'])->firstWhere('name', $filter['name']);
                        $excludedCount = $filterStat['excluded_count'] ?? 0;
                    @endphp
                    @if($excludedCount > 0)
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $darkMode ? 'bg-red-900/40 text-red-300' : 'bg-red-100 text-red-700' }}">
                            -{{ $excludedCount }}
                        </span>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
    
    <!-- Filter Statistics -->
    @if($filterStats)
        <div class="space-y-3">
            <h3 class="text-sm font-medium {{ $darkMode ? 'text-gray-300' : 'text-gray-700' }}">
                Filter Impact
            </h3>
            
            <div class="p-3 {{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200' }} border rounded-lg space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">Original Resources:</span>
                    <span class="{{ $darkMode ? 'text-gray-200' : 'text-gray-700' }} font-medium">
                        {{ number_format($filterStats['original_count']) }}
                    </span>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">After Filtering:</span>
                    <span class="{{ $darkMode ? 'text-gray-200' : 'text-gray-700' }} font-medium">
                        {{ number_format($filterStats['final_count']) }}
                    </span>
                </div>
                
                @if($filterStats['total_excluded'] > 0)
                    <div class="flex justify-between text-sm">
                        <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">Filtered Out:</span>
                        <span class="text-red-600 dark:text-red-400 font-medium">
                            {{ number_format($filterStats['total_excluded']) }}
                        </span>
                    </div>
                @endif
                
                <!-- Filter Efficiency -->
                @php
                    $efficiency = $filterStats['original_count'] > 0 
                        ? round(($filterStats['final_count'] / $filterStats['original_count']) * 100, 1)
                        : 0;
                @endphp
                <div class="pt-2 border-t {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
                    <div class="flex justify-between text-sm">
                        <span class="{{ $darkMode ? 'text-gray-400' : 'text-gray-500' }}">Showing:</span>
                        <span class="{{ $efficiency > 50 ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400' }} font-medium">
                            {{ $efficiency }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Advanced Filters (Collapsible) -->
    <div x-data="{ expanded: false }" class="space-y-3">
        <button 
            @click="expanded = !expanded"
            class="flex items-center justify-between w-full text-sm font-medium {{ $darkMode ? 'text-gray-300 hover:text-gray-200' : 'text-gray-700 hover:text-gray-900' }} transition-colors">
            <span>Advanced Filters</span>
            <x-lucide-icon name="chevron-down" 
                class="h-4 w-4 transition-transform duration-200" 
                :class="{ 'rotate-180': expanded }" />
        </button>
        
        <div x-show="expanded" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 max-h-0"
             x-transition:enter-end="opacity-100 max-h-96"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 max-h-96"
             x-transition:leave-end="opacity-0 max-h-0"
             class="space-y-4 overflow-hidden">
            
            <!-- Memory Range Filter -->
            <div class="space-y-2">
                <label class="block text-sm font-medium {{ $darkMode ? 'text-gray-300' : 'text-gray-700' }}">
                    GPU Memory (GB)
                </label>
                <div class="flex items-center space-x-2">
                    <input 
                        type="range" 
                        min="0" 
                        max="128" 
                        step="8"
                        value="0"
                        class="flex-1"
                        onchange="updateMemoryFilter(this.value)">
                    <span class="text-sm {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }} w-12">
                        <span id="memory-value">0</span>GB+
                    </span>
                </div>
            </div>
            
            <!-- Core Count Filter -->
            <div class="space-y-2">
                <label class="block text-sm font-medium {{ $darkMode ? 'text-gray-300' : 'text-gray-700' }}">
                    CPU Cores
                </label>
                <div class="flex items-center space-x-2">
                    <input 
                        type="range" 
                        min="0" 
                        max="128" 
                        step="4"
                        value="0"
                        class="flex-1"
                        onchange="updateCoreFilter(this.value)">
                    <span class="text-sm {{ $darkMode ? 'text-gray-400' : 'text-gray-500' }} w-12">
                        <span id="core-value">0</span>+
                    </span>
                </div>
            </div>
            
            <!-- Location Filter -->
            <div class="space-y-2">
                <label class="block text-sm font-medium {{ $darkMode ? 'text-gray-300' : 'text-gray-700' }}">
                    Location
                </label>
                <select 
                    onchange="updateLocationFilter(this.value)"
                    class="w-full px-3 py-2 text-sm border rounded-lg {{ $darkMode ? 'bg-gray-800 border-gray-700 text-gray-200' : 'bg-white border-gray-300 text-gray-900' }} focus:ring-2 focus:ring-blue-500">
                    <option value="">All Locations</option>
                    <option value="US">United States</option>
                    <option value="EU">Europe</option>
                    <option value="ASIA">Asia</option>
                    <option value="OTHER">Other</option>
                </select>
            </div>
        </div>
    </div>
</div>

<script>
function updateFilter(type, value) {
    const url = new URL(window.location);
    url.searchParams.set(type, value);
    window.location.href = url.toString();
}

function toggleSystemFilter(filterName) {
    fetch(`/api/filters/${filterName}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error toggling filter:', error);
    });
}

function resetAllFilters() {
    // Reset URL filters
    const url = new URL(window.location);
    url.searchParams.delete('type');
    url.searchParams.delete('ownership');
    
    // Reset system filters
    fetch('/api/filters/reset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(() => {
        window.location.href = url.toString();
    });
}

function updateMemoryFilter(value) {
    document.getElementById('memory-value').textContent = value;
    // TODO: Implement memory filtering
}

function updateCoreFilter(value) {
    document.getElementById('core-value').textContent = value;
    // TODO: Implement core filtering
}

function updateLocationFilter(value) {
    // TODO: Implement location filtering
}
</script>