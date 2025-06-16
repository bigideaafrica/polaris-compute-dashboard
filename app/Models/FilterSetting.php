<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilterSetting extends Model
{
    protected $fillable = [
        'name',
        'description',
        'enabled',
        'config',
        'order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
        'order' => 'integer',
    ];

    /**
     * Scope for enabled filters
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope for ordered filters
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get all enabled filters in order
     */
    public static function getEnabledFilters()
    {
        return static::enabled()->ordered()->get();
    }

    /**
     * Check if a specific filter is enabled
     */
    public static function isFilterEnabled(string $filterName): bool
    {
        return static::where('name', $filterName)
                    ->where('enabled', true)
                    ->exists();
    }

    /**
     * Get filter configuration
     */
    public static function getFilterConfig(string $filterName): ?array
    {
        $filter = static::where('name', $filterName)->first();
        return $filter ? $filter->config : null;
    }

    /**
     * Toggle filter status
     */
    public static function toggleFilter(string $filterName): bool
    {
        $filter = static::where('name', $filterName)->first();
        if (!$filter) {
            return false;
        }
        
        $filter->enabled = !$filter->enabled;
        $filter->save();
        
        return $filter->enabled;
    }

    /**
     * Update filter configuration
     */
    public static function updateFilterConfig(string $filterName, array $config): bool
    {
        $filter = static::where('name', $filterName)->first();
        if (!$filter) {
            return false;
        }
        
        $filter->config = array_merge($filter->config ?? [], $config);
        $filter->save();
        
        return true;
    }
}