<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\FilterSetting;

class ComputeResourceFilterService
{
    /**
     * Apply all enabled filters to compute resources
     */
    public function filterResources(array $resources, array $userFilters = []): array
    {
        Log::info('[FilterService] Starting filter process', [
            'total_resources' => count($resources),
            'user_filters' => $userFilters
        ]);

        $filteredResources = $resources;
        $filterStats = [
            'original_count' => count($resources),
            'filters_applied' => [],
        ];

        // Get enabled system filters
        $enabledFilters = FilterSetting::getEnabledFilters();

        foreach ($enabledFilters as $filter) {
            $beforeCount = count($filteredResources);
            
            switch ($filter->name) {
                case 'fake_gpu_filter':
                    $filteredResources = $this->applyFakeGpuFilter($filteredResources);
                    break;
                    
                case 'storage_filter':
                    $filteredResources = $this->applyStorageFilter($filteredResources);
                    break;
                    
                case 'verification_filter':
                    $filteredResources = $this->applyVerificationFilter($filteredResources);
                    break;
                    
                case 'activity_filter':
                    $filteredResources = $this->applyActivityFilter($filteredResources);
                    break;
                    
                case 'monitoring_filter':
                    $filteredResources = $this->applyMonitoringFilter($filteredResources);
                    break;
            }
            
            $afterCount = count($filteredResources);
            $excluded = $beforeCount - $afterCount;
            
            $filterStats['filters_applied'][] = [
                'name' => $filter->name,
                'description' => $filter->description,
                'before_count' => $beforeCount,
                'after_count' => $afterCount,
                'excluded_count' => $excluded,
                'enabled' => true
            ];
            
            Log::info("[FilterService] Applied {$filter->name}: {$beforeCount} -> {$afterCount} (excluded: {$excluded})");
        }

        // Apply user filters (type, ownership)
        if (isset($userFilters['type'])) {
            $beforeCount = count($filteredResources);
            $filteredResources = $this->applyTypeFilter($filteredResources, $userFilters['type']);
            $afterCount = count($filteredResources);
            
            $filterStats['filters_applied'][] = [
                'name' => 'type_filter',
                'description' => "Filter by type: {$userFilters['type']}",
                'before_count' => $beforeCount,
                'after_count' => $afterCount,
                'excluded_count' => $beforeCount - $afterCount,
                'enabled' => true
            ];
        }

        if (isset($userFilters['ownership']) && $userFilters['ownership'] !== 'all') {
            $beforeCount = count($filteredResources);
            $filteredResources = $this->applyOwnershipFilter($filteredResources, $userFilters['ownership'], $userFilters['user_id'] ?? null);
            $afterCount = count($filteredResources);
            
            $filterStats['filters_applied'][] = [
                'name' => 'ownership_filter',
                'description' => "Filter by ownership: {$userFilters['ownership']}",
                'before_count' => $beforeCount,
                'after_count' => $afterCount,
                'excluded_count' => $beforeCount - $afterCount,
                'enabled' => true
            ];
        }

        $filterStats['final_count'] = count($filteredResources);
        $filterStats['total_excluded'] = $filterStats['original_count'] - $filterStats['final_count'];

        Log::info('[FilterService] Filter process completed', $filterStats);

        return [
            'resources' => $filteredResources,
            'stats' => $filterStats
        ];
    }

    /**
     * Remove resources with "No discrete GPU detected"
     */
    private function applyFakeGpuFilter(array $resources): array
    {
        return array_filter($resources, function ($resource) {
            if ($resource['resource_type'] !== 'GPU') {
                return true; // Keep CPU resources
            }
            
            $gpuSpecs = $resource['gpu_specs'] ?? [];
            $modelName = $gpuSpecs['model_name'] ?? $gpuSpecs['gpu_name'] ?? '';
            
            return $modelName !== 'No discrete GPU detected';
        });
    }

    /**
     * Remove resources with invalid storage
     */
    private function applyStorageFilter(array $resources): array
    {
        return array_filter($resources, function ($resource) {
            $storage = $resource['storage'] ?? [];
            $totalGb = $storage['total_gb'] ?? null;
            
            return $totalGb !== null && 
                   $totalGb !== '' && 
                   is_numeric($totalGb) && 
                   $totalGb > 0;
        });
    }

    /**
     * Keep only verified resources
     */
    private function applyVerificationFilter(array $resources): array
    {
        return array_filter($resources, function ($resource) {
            return ($resource['validation_status'] ?? '') === 'verified';
        });
    }

    /**
     * Keep only active resources
     */
    private function applyActivityFilter(array $resources): array
    {
        return array_filter($resources, function ($resource) {
            return ($resource['is_active'] ?? false) === true;
        });
    }

    /**
     * Keep only resources with healthy monitoring
     */
    private function applyMonitoringFilter(array $resources): array
    {
        return array_filter($resources, function ($resource) {
            $monitoring = $resource['monitoring_status'] ?? [];
            
            if (empty($monitoring)) {
                return false;
            }
            
            $authOk = ($monitoring['auth']['status'] ?? '') === 'ok';
            $connOk = ($monitoring['conn']['status'] ?? '') === 'ok';
            $dockerRunning = ($monitoring['docker']['running'] ?? false) === true;
            $dockerUserGroup = ($monitoring['docker']['user_group'] ?? false) === true;
            
            return $authOk && $connOk && $dockerRunning && $dockerUserGroup;
        });
    }

    /**
     * Filter by resource type (GPU/CPU/All)
     */
    private function applyTypeFilter(array $resources, string $type): array
    {
        if ($type === 'all') {
            return $resources;
        }
        
        return array_filter($resources, function ($resource) use ($type) {
            if ($type === 'gpu') {
                return $resource['resource_type'] === 'GPU';
            }
            
            if ($type === 'cpu') {
                return $resource['resource_type'] === 'CPU';
            }
            
            return true;
        });
    }

    /**
     * Filter by ownership (mine/all)
     */
    private function applyOwnershipFilter(array $resources, string $ownership, ?string $userId): array
    {
        if ($ownership === 'all' || !$userId) {
            return $resources;
        }
        
        if ($ownership === 'mine') {
            return array_filter($resources, function ($resource) use ($userId) {
                $rentalStatus = $resource['rental_status'] ?? [];
                return ($rentalStatus['user_id'] ?? '') === $userId;
            });
        }
        
        return $resources;
    }

    /**
     * Sort resources (GPUs first, then by memory/cores)
     */
    public function sortResources(array $resources): array
    {
        usort($resources, function ($a, $b) {
            // GPUs first
            if ($a['resource_type'] === 'GPU' && $b['resource_type'] !== 'GPU') {
                return -1;
            }
            if ($a['resource_type'] !== 'GPU' && $b['resource_type'] === 'GPU') {
                return 1;
            }
            
            // If both are GPUs, sort by VRAM (higher first)
            if ($a['resource_type'] === 'GPU' && $b['resource_type'] === 'GPU') {
                $vramA = $this->extractMemoryValue($a['gpu_specs']['memory'] ?? '0');
                $vramB = $this->extractMemoryValue($b['gpu_specs']['memory'] ?? '0');
                return $vramB - $vramA;
            }
            
            // If both are CPUs, sort by core count (higher first)
            if ($a['resource_type'] === 'CPU' && $b['resource_type'] === 'CPU') {
                $coresA = $a['cpu_specs']['total_cores'] ?? 0;
                $coresB = $b['cpu_specs']['total_cores'] ?? 0;
                return $coresB - $coresA;
            }
            
            return 0;
        });
        
        return $resources;
    }

    /**
     * Extract numeric memory value from string (e.g., "24GB" -> 24)
     */
    private function extractMemoryValue(string $memory): float
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*GB/i', $memory, $matches)) {
            return (float) $matches[1];
        }
        return 0;
    }

    /**
     * Get filter statistics for debugging
     */
    public function getFilterStats(array $resources): array
    {
        $stats = [
            'total_resources' => count($resources),
            'by_type' => [
                'GPU' => count(array_filter($resources, fn($r) => $r['resource_type'] === 'GPU')),
                'CPU' => count(array_filter($resources, fn($r) => $r['resource_type'] === 'CPU')),
            ],
            'by_status' => [
                'verified' => count(array_filter($resources, fn($r) => $r['validation_status'] === 'verified')),
                'pending' => count(array_filter($resources, fn($r) => $r['validation_status'] === 'pending')),
                'rejected' => count(array_filter($resources, fn($r) => $r['validation_status'] === 'rejected')),
            ],
            'by_activity' => [
                'active' => count(array_filter($resources, fn($r) => $r['is_active'] === true)),
                'inactive' => count(array_filter($resources, fn($r) => $r['is_active'] === false)),
            ],
            'fake_gpus' => 0,
            'invalid_storage' => 0,
            'unhealthy_monitoring' => 0,
        ];

        // Count problematic resources
        foreach ($resources as $resource) {
            // Fake GPUs
            if ($resource['resource_type'] === 'GPU') {
                $gpuSpecs = $resource['gpu_specs'] ?? [];
                $modelName = $gpuSpecs['model_name'] ?? $gpuSpecs['gpu_name'] ?? '';
                if ($modelName === 'No discrete GPU detected') {
                    $stats['fake_gpus']++;
                }
            }
            
            // Invalid storage
            $storage = $resource['storage'] ?? [];
            $totalGb = $storage['total_gb'] ?? null;
            if (!$totalGb || !is_numeric($totalGb) || $totalGb <= 0) {
                $stats['invalid_storage']++;
            }
            
            // Unhealthy monitoring
            $monitoring = $resource['monitoring_status'] ?? [];
            if (!empty($monitoring)) {
                $authOk = ($monitoring['auth']['status'] ?? '') === 'ok';
                $connOk = ($monitoring['conn']['status'] ?? '') === 'ok';
                $dockerRunning = ($monitoring['docker']['running'] ?? false) === true;
                $dockerUserGroup = ($monitoring['docker']['user_group'] ?? false) === true;
                
                if (!($authOk && $connOk && $dockerRunning && $dockerUserGroup)) {
                    $stats['unhealthy_monitoring']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Get available filter toggles for UI
     */
    public function getFilterToggles(): array
    {
        return FilterSetting::all()->map(function ($filter) {
            return [
                'name' => $filter->name,
                'description' => $filter->description,
                'enabled' => $filter->enabled,
                'config' => $filter->config,
            ];
        })->toArray();
    }

    /**
     * Toggle a filter on/off
     */
    public function toggleFilter(string $filterName): bool
    {
        return FilterSetting::toggleFilter($filterName);
    }
}