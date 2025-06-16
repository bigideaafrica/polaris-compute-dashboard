<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ComputeResourceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Main dashboard route
Route::get('/', [ComputeResourceController::class, 'index'])->name('dashboard');

// API Routes
Route::prefix('api')->group(function () {
    // Compute resources API
    Route::prefix('compute')->group(function () {
        Route::get('/', [ComputeResourceController::class, 'api'])->name('api.compute.index');
        Route::get('/{id}', [ComputeResourceController::class, 'show'])->name('api.compute.show');
        Route::post('/refresh', [ComputeResourceController::class, 'refresh'])->name('api.compute.refresh');
        Route::get('/export', [ComputeResourceController::class, 'export'])->name('api.compute.export');
        Route::get('/test', [ComputeResourceController::class, 'testApi'])->name('api.compute.test');
        Route::get('/stats/filters', [ComputeResourceController::class, 'filterStats'])->name('api.compute.filter-stats');
    });
    
    // Filter management API
    Route::prefix('filters')->group(function () {
        Route::post('/{filterName}/toggle', [ComputeResourceController::class, 'toggleFilter'])->name('api.filters.toggle');
        Route::post('/reset', [ComputeResourceController::class, 'resetFilters'])->name('api.filters.reset');
    });
});

// Legacy/alternative routes for compatibility
Route::get('/dashboard', [ComputeResourceController::class, 'index'])->name('dashboard.legacy');
Route::get('/compute', [ComputeResourceController::class, 'index'])->name('compute');
Route::get('/resources', [ComputeResourceController::class, 'index'])->name('resources');