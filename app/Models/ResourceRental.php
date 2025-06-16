<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class ResourceRental extends Model
{
    protected $fillable = [
        'resource_id',
        'user_id',
        'status',
        'rental_start_date',
        'rental_end_date',
        'container_info',
        'database_id',
        'container_id',
        'duration',
        'terminated_at',
    ];

    protected $casts = [
        'container_info' => 'array',
        'duration' => 'array',
        'rental_start_date' => 'datetime',
        'rental_end_date' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    /**
     * Get the compute resource this rental belongs to
     */
    public function computeResource(): BelongsTo
    {
        return $this->belongsTo(ComputeResource::class, 'resource_id');
    }

    /**
     * Get formatted time remaining
     */
    protected function timeRemaining(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== 'active' || !$this->rental_end_date) {
                    return '00:00:00';
                }
                
                $now = Carbon::now();
                $endDate = Carbon::parse($this->rental_end_date);
                
                if ($endDate->isPast()) {
                    return '00:00:00';
                }
                
                $diff = $now->diff($endDate);
                return sprintf('%02d:%02d:%02d', 
                    $diff->days * 24 + $diff->h, 
                    $diff->i, 
                    $diff->s
                );
            }
        );
    }

    /**
     * Get runtime duration since start
     */
    protected function runtime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->rental_start_date) {
                    return '00:00:00';
                }
                
                $start = Carbon::parse($this->rental_start_date);
                $end = $this->terminated_at ? Carbon::parse($this->terminated_at) : Carbon::now();
                
                $diff = $start->diff($end);
                return sprintf('%02d:%02d:%02d', 
                    $diff->days * 24 + $diff->h, 
                    $diff->i, 
                    $diff->s
                );
            }
        );
    }

    /**
     * Get connection details for SSH
     */
    protected function sshCommand(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->container_info) {
                    return null;
                }
                
                $host = $this->container_info['host'] ?? null;
                $port = $this->container_info['ssh_port'] ?? null;
                $username = $this->container_info['username'] ?? null;
                
                if (!$host || !$port || !$username) {
                    return null;
                }
                
                return "ssh {$username}@{$host} -p {$port}";
            }
        );
    }

    /**
     * Check if rental is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->rental_end_date && 
               Carbon::parse($this->rental_end_date)->isFuture();
    }

    /**
     * Check if rental is expired
     */
    public function isExpired(): bool
    {
        return $this->rental_end_date && 
               Carbon::parse($this->rental_end_date)->isPast();
    }

    /**
     * Scope for active rentals
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('rental_end_date', '>', now());
    }

    /**
     * Scope for rentals by user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for rentals of specific resource
     */
    public function scopeForResource($query, string $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }
}