@props(['darkMode' => false])

<div class="animate-pulse {{ $darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200' }} border rounded-lg">
    <!-- Header -->
    <div class="p-4 border-b {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
        <div class="flex items-center space-x-2">
            <div class="h-5 w-5 {{ $darkMode ? 'bg-gray-700' : 'bg-gray-200' }} rounded"></div>
            <div class="h-4 {{ $darkMode ? 'bg-gray-700' : 'bg-gray-200' }} rounded w-3/4"></div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="p-4 space-y-3">
        <!-- Spec lines -->
        @for($i = 0; $i < 4; $i++)
            <div class="flex justify-between items-center">
                <div class="h-3 {{ $darkMode ? 'bg-gray-700' : 'bg-gray-200' }} rounded w-1/3"></div>
                <div class="h-3 {{ $darkMode ? 'bg-gray-700' : 'bg-gray-200' }} rounded w-1/4"></div>
            </div>
        @endfor
    </div>
    
    <!-- Pricing -->
    <div class="px-4 py-3 {{ $darkMode ? 'bg-gray-750' : 'bg-gray-50' }} border-t {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
        <div class="flex items-center justify-between">
            <div class="h-3 {{ $darkMode ? 'bg-gray-700' : 'bg-gray-200' }} rounded w-1/3"></div>
            <div class="h-4 {{ $darkMode ? 'bg-gray-700' : 'bg-gray-200' }} rounded w-1/4"></div>
        </div>
    </div>
    
    <!-- Button -->
    <div class="p-4 border-t {{ $darkMode ? 'border-gray-700' : 'border-gray-200' }}">
        <div class="h-10 {{ $darkMode ? 'bg-gray-700' : 'bg-gray-200' }} rounded"></div>
    </div>
</div>