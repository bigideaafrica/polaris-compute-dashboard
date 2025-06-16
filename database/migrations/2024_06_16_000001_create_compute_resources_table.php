<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compute_resources', function (Blueprint $table) {
            $table->string('id')->primary(); // UUID from API
            $table->enum('resource_type', ['GPU', 'CPU']);
            $table->json('gpu_specs')->nullable();
            $table->json('cpu_specs')->nullable();
            $table->string('ram', 20)->nullable();
            $table->json('storage')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('validation_status', ['verified', 'pending', 'rejected'])->default('pending');
            $table->decimal('hourly_price', 8, 2)->default(0);
            $table->string('location', 100)->nullable();
            $table->json('monitoring_status')->nullable();
            $table->json('network')->nullable();
            $table->string('miner_id')->nullable();
            $table->string('owner_firebase_uid')->nullable();
            $table->integer('gpu_count')->default(1);
            $table->integer('cpu_count')->default(1);
            $table->json('root_access_details')->nullable();
            $table->string('root_access_status')->nullable();
            $table->timestamp('last_monitored_at')->nullable();
            $table->timestamps();
            
            $table->index(['resource_type', 'validation_status', 'is_active']);
            $table->index(['miner_id']);
            $table->index(['owner_firebase_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compute_resources');
    }
};