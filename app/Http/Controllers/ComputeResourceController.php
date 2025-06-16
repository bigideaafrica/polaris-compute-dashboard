<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\PolarisApiService;
use App\Services\ComputeResourceFilterService;
use App\Models\FilterSetting;
use Illuminate\Support\Facades\Log;

class ComputeResourceController extends Controller
{
    private PolarisApiService $apiService;
    private ComputeResourceFilterService $filterService;

    public function __construct(
        PolarisApiService $apiService, 
        ComputeResourceFilterService $filterService
    ) {
        $this->apiService = $apiService;
        $this->filterService = $filterService;
    }

    /**
     * Display the main compute resources dashboard
     */
    public function index(Request $request): View
    {
        try {
            // Get user filters from request
            $userFilters = [
                'type' => $request->get('type', 'all'),
                'ownership' => $request->get('ownership', 'all'),
                'user_id' => $request->user()->id ?? null,
            ];

            // Fetch resources from API
            $forceRefresh = $request->boolean('refresh', false);
            $rawResources = $this->apiService->fetchComputeResources($forceRefresh);

            // Apply filters
            $filterResult = $this->filterService->filterResources($rawResources, $userFilters);
            $filteredResources = $filterResult['resources'];
            $filterStats = $filterResult['stats'];

            // Sort resources
            $sortedResources = $this->filterService->sortResources($filteredResources);

            // Get filter settings for UI
            $filterSettings = $this->filterService->getFilterToggles();

            return view('dashboard', [
                'resources' => array_values($sortedResources), // Re-index array
                'filterStats' => $filterStats,
                'filterSettings' => $filterSettings,
                'activeFilters' => $userFilters,
                'darkMode' => $request->boolean('dark', false),
                'currentUser' => $request->user(),
                'pageTitle' => 'Polaris Compute Resources',
                'resourceCount' => count($sortedResources),
                'apiStats' => $this->apiService->getApiStats(),
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] Error loading dashboard: ' . $e->getMessage());
            
            return view('dashboard', [
                'resources' => [],
                'filterStats' => null,
                'filterSettings' => [],
                'activeFilters' => $userFilters ?? [],
                'darkMode' => $request->boolean('dark', false),
                'currentUser' => $request->user(),
                'pageTitle' => 'Polaris Compute Resources',
                'resourceCount' => 0,
                'error' => 'Failed to load compute resources: ' . $e->getMessage(),
                'apiStats' => [],
            ]);
        }
    }

    /**
     * API endpoint to fetch filtered resources
     */
    public function api(Request $request): JsonResponse
    {
        try {
            $userFilters = [
                'type' => $request->get('type', 'all'),
                'ownership' => $request->get('ownership', 'all'),
                'user_id' => $request->user()->id ?? null,
            ];

            $forceRefresh = $request->boolean('refresh', false);
            $rawResources = $this->apiService->fetchComputeResources($forceRefresh);

            $filterResult = $this->filterService->filterResources($rawResources, $userFilters);
            $filteredResources = $filterResult['resources'];
            $filterStats = $filterResult['stats'];

            $sortedResources = $this->filterService->sortResources($filteredResources);

            return response()->json([
                'success' => true,
                'data' => [
                    'resources' => array_values($sortedResources),
                    'stats' => $filterStats,
                    'count' => count($sortedResources),
                    'timestamp' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] API error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch compute resources',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get resource details by ID
     */
    public function show(string $id): JsonResponse
    {
        try {
            $resources = $this->apiService->fetchComputeResources();
            $resource = collect($resources)->firstWhere('id', $id);

            if (!$resource) {
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $resource
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] Error fetching resource ' . $id . ': ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch resource details'
            ], 500);
        }
    }

    /**
     * Toggle a system filter
     */
    public function toggleFilter(string $filterName): JsonResponse
    {
        try {
            $newStatus = FilterSetting::toggleFilter($filterName);
            
            Log::info("[ComputeController] Toggled filter {$filterName} to " . ($newStatus ? 'enabled' : 'disabled'));
            
            return response()->json([
                'success' => true,
                'filter' => $filterName,
                'enabled' => $newStatus,
                'message' => "Filter {$filterName} " . ($newStatus ? 'enabled' : 'disabled')
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] Error toggling filter ' . $filterName . ': ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to toggle filter'
            ], 500);
        }
    }

    /**
     * Reset all filters to default
     */
    public function resetFilters(): JsonResponse
    {
        try {
            // Reset all filters to enabled (default state)
            FilterSetting::query()->update(['enabled' => true]);
            
            Log::info('[ComputeController] Reset all filters to default state');
            
            return response()->json([
                'success' => true,
                'message' => 'All filters reset to default state'
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] Error resetting filters: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to reset filters'
            ], 500);
        }
    }

    /**
     * Get filter statistics
     */
    public function filterStats(): JsonResponse
    {
        try {
            $rawResources = $this->apiService->fetchComputeResources();
            $stats = $this->filterService->getFilterStats($rawResources);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] Error getting filter stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get filter statistics'
            ], 500);
        }
    }

    /**
     * Force refresh resources from API
     */
    public function refresh(): JsonResponse
    {
        try {
            $resources = $this->apiService->fetchComputeResources(true);
            $synced = $this->apiService->syncResourcesToDatabase($resources);
            
            Log::info("[ComputeController] Force refresh completed: {$synced} resources synced");
            
            return response()->json([
                'success' => true,
                'data' => [
                    'resources_count' => count($resources),
                    'synced_count' => $synced,
                    'timestamp' => now()->toISOString(),
                ],
                'message' => "Refreshed {$synced} resources from API"
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] Error during refresh: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh resources',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test API connectivity
     */
    public function testApi(): JsonResponse
    {
        try {
            $connectivity = $this->apiService->testConnectivity();
            $stats = $this->apiService->getApiStats();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'connectivity' => $connectivity,
                    'stats' => $stats,
                    'timestamp' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] Error testing API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'API test failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export resources data
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'json');
            $userFilters = [
                'type' => $request->get('type', 'all'),
                'ownership' => $request->get('ownership', 'all'),
                'user_id' => $request->user()->id ?? null,
            ];

            $rawResources = $this->apiService->fetchComputeResources();
            $filterResult = $this->filterService->filterResources($rawResources, $userFilters);
            $resources = $this->filterService->sortResources($filterResult['resources']);

            $exportData = [
                'metadata' => [
                    'exported_at' => now()->toISOString(),
                    'total_resources' => count($resources),
                    'filters_applied' => $filterResult['stats']['filters_applied'] ?? [],
                    'export_format' => $format,
                ],
                'resources' => $resources,
            ];

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'filename' => "polaris-compute-resources-" . now()->format('Y-m-d-His') . ".{$format}"
            ]);

        } catch (\Exception $e) {
            Log::error('[ComputeController] Error exporting resources: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to export resources'
            ], 500);
        }
    }
}