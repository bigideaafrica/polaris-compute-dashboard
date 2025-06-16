<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ComputeResource extends Model
{
    protected $table = 'compute_resources';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'resource_type',
        'gpu_specs',
        'cpu_specs',
        'ram',
        'storage',
        'is_active',
        'validation_status',
        'hourly_price',
        'location',
        'monitoring_status',
        'network',
        'miner_id',
        'owner_firebase_uid',
        'gpu_count',
        'cpu_count',
        'root_access_details',
        'root_access_status',
        'last_monitored_at',
    ];

    protected $casts = [
        'gpu_specs' => 'array',
        'cpu_specs' => 'array',
        'storage' => 'array',
        'monitoring_status' => 'array',
        'network' => 'array',
        'root_access_details' => 'array',
        'is_active' => 'boolean',
        'hourly_price' => 'decimal:2',
        'last_monitored_at' => 'datetime',
    ];

    /**
     * Get the rentals for this resource
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(ResourceRental::class, 'resource_id');
    }

    /**
     * Get the active rental for this resource
     */
    public function activeRental()
    {
        return $this->rentals()->where('status', 'active')->first();
    }

    /**
     * Check if resource is currently rented
     */
    public function isRented(): bool
    {
        return $this->rentals()->where('status', 'active')->exists();
    }

    /**
     * Check if resource is rented by specific user
     */
    public function isRentedBy(string $userId): bool
    {
        return $this->rentals()
            ->where('status', 'active')
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get formatted GPU memory size
     */
    protected function formattedGpuMemory(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->gpu_specs || !isset($this->gpu_specs['memory'])) {
                    return 'N/A';
                }
                
                $memory = $this->gpu_specs['memory'];
                // Handle different formats: "24GB", "24GB GDDR6X", etc.
                if (preg_match('/(\d+(?:\.\d+)?)\s*GB/i', $memory, $matches)) {
                    return $matches[1] . 'GB';
                }
                
                return $memory;
            }
        );
    }

    /**
     * Get formatted CPU cores
     */
    protected function formattedCpuCores(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->cpu_specs) {
                    return 'N/A';
                }
                
                return $this->cpu_specs['total_cores'] ?? 
                       $this->cpu_specs['cores_per_cpu'] ?? 
                       'N/A';
            }
        );
    }

    /**
     * Get formatted storage
     */
    protected function formattedStorage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->storage || !isset($this->storage['total_gb'])) {
                    return 'N/A';
                }
                
                $gb = $this->storage['total_gb'];
                $type = $this->storage['type'] ?? '';
                
                if ($gb >= 1024) {
                    $tb = round($gb / 1024, 1);
                    return $tb . 'TB ' . $type;
                }
                
                return $gb . 'GB ' . $type;
            }
        );
    }

    /**
     * Get monitoring health status
     */
    protected function monitoringHealth(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->monitoring_status) {
                    return 'unknown';
                }
                
                $status = $this->monitoring_status;
                
                $authOk = ($status['auth']['status'] ?? '') === 'ok';
                $connOk = ($status['conn']['status'] ?? '') === 'ok';
                $dockerRunning = ($status['docker']['running'] ?? false) === true;
                $dockerUserGroup = ($status['docker']['user_group'] ?? false) === true;
                
                if ($authOk && $connOk && $dockerRunning && $dockerUserGroup) {
                    return 'healthy';
                }
                
                if (!$authOk || !$connOk) {
                    return 'connection_issues';
                }
                
                if (!$dockerRunning || !$dockerUserGroup) {
                    return 'docker_issues';
                }
                
                return 'degraded';
            }
        );
    }

    /**
     * Check if resource has fake GPU
     */
    public function hasFakeGpu(): bool
    {
        if ($this->resource_type !== 'GPU' || !$this->gpu_specs) {
            return false;
        }
        
        $gpuName = $this->gpu_specs['model_name'] ?? $this->gpu_specs['gpu_name'] ?? '';
        return $gpuName === 'No discrete GPU detected';
    }

    /**
     * Check if resource has valid storage
     */
    public function hasValidStorage(): bool
    {
        if (!$this->storage || !isset($this->storage['total_gb'])) {
            return false;
        }
        
        $totalGb = $this->storage['total_gb'];
        return is_numeric($totalGb) && $totalGb > 0;
    }

    /**
     * Scope for filtering by resource type
     */
    public function scopeOfType($query, string $type)
    {
        if ($type === 'all') {
            return $query;
        }
        
        return $query->where('resource_type', strtoupper($type));
    }

    /**
     * Scope for filtering verified resources
     */
    public function scopeVerified($query)
    {
        return $query->where('validation_status', 'verified');
    }

    /**
     * Scope for filtering active resources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for filtering healthy resources
     */
    public function scopeHealthy($query)
    {
        return $query->whereRaw("
            JSON_EXTRACT(monitoring_status, '$.auth.status') = 'ok' AND
            JSON_EXTRACT(monitoring_status, '$.conn.status') = 'ok' AND
            JSON_EXTRACT(monitoring_status, '$.docker.running') = true AND
            JSON_EXTRACT(monitoring_status, '$.docker.user_group') = true
        ");
    }

    /**
     * Scope for filtering out fake GPUs
     */
    public function scopeRealGpus($query)
    {
        return $query->where(function ($q) {
            $q->where('resource_type', 'CPU')
              ->orWhere(function ($subQ) {
                  $subQ->where('resource_type', 'GPU')
                       ->whereRaw("JSON_EXTRACT(gpu_specs, '$.model_name') != 'No discrete GPU detected'")
                       ->whereRaw("JSON_EXTRACT(gpu_specs, '$.gpu_name') != 'No discrete GPU detected'");
              });
        });
    }

    /**
     * Scope for filtering resources with valid storage
     */
    public function scopeValidStorage($query)
    {
        return $query->whereRaw("
            JSON_EXTRACT(storage, '$.total_gb') IS NOT NULL AND
            JSON_EXTRACT(storage, '$.total_gb') > 0
        ");
    }
}