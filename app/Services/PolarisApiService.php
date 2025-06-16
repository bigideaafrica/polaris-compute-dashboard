<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\ComputeResource;
use App\Models\ResourceRental;

class PolarisApiService
{
    private string $baseUrl;
    private array $headers;
    private int $timeout;
    private int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('polaris.api.base_url');
        $this->headers = config('polaris.api.headers');
        $this->timeout = config('polaris.api.timeout', 30);
        $this->cacheTtl = config('polaris.refresh.cache_ttl', 30);
    }

    /**
     * Fetch compute resources from Polaris API
     */
    public function fetchComputeResources(bool $forceRefresh = false): array
    {
        $cacheKey = 'polaris_compute_resources';
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            Log::info('[PolarisAPI] Using cached compute resources');
            return Cache::get($cacheKey);
        }

        try {
            $url = $this->baseUrl . config('polaris.api.endpoints.compute_resources');
            
            Log::info('[PolarisAPI] Fetching compute resources from: ' . $url);
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->headers)
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception("HTTP error! status: {$response->status()}, message: {$response->body()}");
            }

            $data = $response->json();
            
            if (!isset($data['success']) || !$data['success'] || !isset($data['data']['compute_resources'])) {
                throw new \Exception('Invalid API response format');
            }

            $resources = $data['data']['compute_resources'];
            
            Log::info('[PolarisAPI] Fetched ' . count($resources) . ' compute resources');

            // Transform and cache the resources
            $transformedResources = $this->transformResources($resources);
            Cache::put($cacheKey, $transformedResources, $this->cacheTtl);

            return $transformedResources;

        } catch (\Exception $e) {
            Log::error('[PolarisAPI] Error fetching compute resources: ' . $e->getMessage());
            
            // Try alternative endpoints
            foreach (config('polaris.api.alternative_endpoints', []) as $endpoint) {
                try {
                    $altUrl = $this->baseUrl . $endpoint;
                    Log::info('[PolarisAPI] Trying alternative endpoint: ' . $altUrl);
                    
                    $response = Http::timeout($this->timeout)
                        ->withHeaders($this->headers)
                        ->get($altUrl);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['data']['compute_resources'])) {
                            $resources = $data['data']['compute_resources'];
                            $transformedResources = $this->transformResources($resources);
                            Cache::put($cacheKey, $transformedResources, $this->cacheTtl);
                            return $transformedResources;
                        }
                    }
                } catch (\Exception $altError) {
                    Log::warning('[PolarisAPI] Alternative endpoint failed: ' . $altError->getMessage());
                }
            }
            
            throw $e;
        }
    }

    /**
     * Transform API resources to match application format
     */
    private function transformResources(array $resources): array
    {
        return array_map(function ($resource) {
            return [
                'id' => $resource['id'],
                'resource_type' => $resource['resource_type'],
                'gpu_specs' => $this->transformGpuSpecs($resource['gpu_specs'] ?? null),
                'cpu_specs' => $this->transformCpuSpecs($resource['cpu_specs'] ?? null),
                'ram' => $resource['ram'] ?? 'N/A',
                'storage' => $resource['storage'] ?? null,
                'is_active' => $resource['is_active'] ?? false,
                'validation_status' => $resource['validation_status'] ?? 'pending',
                'hourly_price' => 0, // Set all prices to zero as per original
                'location' => $resource['location'] ?? 'Unknown Location',
                'monitoring_status' => $resource['monitoring_status'] ?? null,
                'network' => $resource['network'] ?? null,
                'miner_id' => $resource['miner_id'] ?? null,
                'owner_firebase_uid' => $resource['owner_firebase_uid'] ?? null,
                'gpu_count' => $resource['gpu_count'] ?? 1,
                'cpu_count' => $resource['cpu_count'] ?? 1,
                'root_access_details' => $resource['root_access_details'] ?? null,
                'root_access_status' => $resource['root_access_status'] ?? null,
                'last_monitored_at' => $resource['last_monitored_at'] ?? null,
                'rental_status' => $resource['rental_status'] ?? null,
                'deployment_status' => $resource['deployment_status'] ?? 'inactive',
                'created_at' => $resource['created_at'] ?? now(),
                'updated_at' => $resource['updated_at'] ?? now(),
            ];
        }, $resources);
    }

    /**
     * Transform GPU specifications
     */
    private function transformGpuSpecs(?array $gpuSpecs): ?array
    {
        if (!$gpuSpecs) {
            return null;
        }

        $gpuName = $this->cleanHardwareName($gpuSpecs['gpu_name'] ?? $gpuSpecs['model_name'] ?? null);
        
        // Handle Virtio GPU case - use CPU name instead
        if ($gpuName && stripos($gpuName, 'virtio gpu') !== false) {
            return null; // This will be handled as CPU-only
        }

        return [
            'gpu_name' => $gpuName,
            'model_name' => $gpuSpecs['model_name'] ?? $gpuName,
            'memory' => $gpuSpecs['memory'] ?? $gpuSpecs['memory_size'] ?? 'N/A',
            'memory_size' => $gpuSpecs['memory_size'] ?? $gpuSpecs['memory'] ?? 'N/A',
            'clock_speed' => $gpuSpecs['clock_speed'] ?? 'N/A',
            'cuda_cores' => $gpuSpecs['cuda_cores'] ?? null,
            'tensor_cores' => $gpuSpecs['tensor_cores'] ?? null,
            'total_gpus' => $gpuSpecs['total_gpus'] ?? 1,
            'architecture' => $gpuSpecs['architecture'] ?? null,
        ];
    }

    /**
     * Transform CPU specifications
     */
    private function transformCpuSpecs(?array $cpuSpecs): ?array
    {
        if (!$cpuSpecs) {
            return null;
        }

        return [
            'cpu_name' => $this->cleanHardwareName($cpuSpecs['cpu_name'] ?? $cpuSpecs['model_name'] ?? null),
            'model_name' => $cpuSpecs['model_name'] ?? $cpuSpecs['cpu_name'] ?? null,
            'total_cores' => $cpuSpecs['total_cores'] ?? $cpuSpecs['cores'] ?? $cpuSpecs['core_count'] ?? null,
            'cores_per_cpu' => $cpuSpecs['cores_per_cpu'] ?? null,
            'clock_speed' => $cpuSpecs['clock_speed'] ?? 
                           ($cpuSpecs['base_clock_ghz'] ? $cpuSpecs['base_clock_ghz'] . 'GHz' : 'Unknown'),
            'architecture' => $cpuSpecs['architecture'] ?? null,
            'threads_per_core' => $cpuSpecs['threads_per_core'] ?? $cpuSpecs['threads'] ?? null,
            'cache_size' => $cpuSpecs['cache_size'] ?? null,
        ];
    }

    /**
     * Clean up hardware names by removing placeholder text
     */
    private function cleanHardwareName(?string $name): ?string
    {
        if (!$name || trim($name) === '') {
            return null;
        }

        // Remove OEM placeholder and clean up spacing
        $cleaned = preg_replace('/To Be Filled By O\.E\.M\./i', '', $name);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);

        return $cleaned ?: null;
    }

    /**
     * Update local database with fetched resources
     */
    public function syncResourcesToDatabase(array $resources): int
    {
        $updated = 0;
        
        foreach ($resources as $resource) {
            try {
                ComputeResource::updateOrCreate(
                    ['id' => $resource['id']],
                    $resource
                );
                $updated++;
            } catch (\Exception $e) {
                Log::error('[PolarisAPI] Error syncing resource ' . $resource['id'] . ': ' . $e->getMessage());
            }
        }
        
        Log::info('[PolarisAPI] Synced ' . $updated . ' resources to database');
        return $updated;
    }

    /**
     * Fetch and sync rental/container data
     */
    public function syncRentalData(): int
    {
        // In the original, rental data comes from the compute resources API
        // This method extracts rental information and creates ResourceRental records
        
        $resources = $this->fetchComputeResources();
        $synced = 0;
        
        foreach ($resources as $resource) {
            if (isset($resource['rental_status']) && $resource['rental_status']) {
                $rentalStatus = $resource['rental_status'];
                
                if (isset($rentalStatus['user_id']) && $rentalStatus['user_id']) {
                    try {
                        ResourceRental::updateOrCreate(
                            [
                                'resource_id' => $resource['id'],
                                'user_id' => $rentalStatus['user_id'],
                            ],
                            [
                                'status' => $rentalStatus['status'] ?? 'active',
                                'rental_start_date' => $rentalStatus['rental_start_date'] ?? now(),
                                'rental_end_date' => $rentalStatus['rental_end_date'] ?? now()->addDays(30),
                                'container_info' => $rentalStatus['container_info'] ?? null,
                                'database_id' => $rentalStatus['database_id'] ?? null,
                                'container_id' => $rentalStatus['container_id'] ?? null,
                                'duration' => $rentalStatus['duration'] ?? null,
                                'terminated_at' => $rentalStatus['terminated_at'] ?? null,
                            ]
                        );
                        $synced++;
                    } catch (\Exception $e) {
                        Log::error('[PolarisAPI] Error syncing rental for resource ' . $resource['id'] . ': ' . $e->getMessage());
                    }
                }
            }
        }
        
        Log::info('[PolarisAPI] Synced ' . $synced . ' rental records');
        return $synced;
    }

    /**
     * Test API connectivity
     */
    public function testConnectivity(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->headers)
                ->head($this->baseUrl);
            
            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_time' => $response->handlerStats()['total_time'] ?? 0,
                'message' => $response->successful() ? 'Connection successful' : 'Connection failed'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 0,
                'response_time' => 0,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get API statistics
     */
    public function getApiStats(): array
    {
        try {
            $resources = $this->fetchComputeResources();
            
            $stats = [
                'total_resources' => count($resources),
                'gpu_resources' => count(array_filter($resources, fn($r) => $r['resource_type'] === 'GPU')),
                'cpu_resources' => count(array_filter($resources, fn($r) => $r['resource_type'] === 'CPU')),
                'verified_resources' => count(array_filter($resources, fn($r) => $r['validation_status'] === 'verified')),
                'active_resources' => count(array_filter($resources, fn($r) => $r['is_active'])),
                'fake_gpu_resources' => 0,
            ];
            
            // Count fake GPUs
            foreach ($resources as $resource) {
                if ($resource['resource_type'] === 'GPU' && 
                    isset($resource['gpu_specs']['model_name']) &&
                    $resource['gpu_specs']['model_name'] === 'No discrete GPU detected') {
                    $stats['fake_gpu_resources']++;
                }
            }
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('[PolarisAPI] Error getting API stats: ' . $e->getMessage());
            return [];
        }
    }
}