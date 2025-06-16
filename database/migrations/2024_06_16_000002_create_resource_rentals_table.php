<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('resource_id');
            $table->string('user_id'); // Firebase UID
            $table->enum('status', ['active', 'paused', 'terminated'])->default('active');
            $table->timestamp('rental_start_date');
            $table->timestamp('rental_end_date');
            $table->json('container_info')->nullable();
            $table->string('database_id')->nullable();
            $table->string('container_id')->nullable();
            $table->json('duration')->nullable(); // {hours: 720, display: "30 days"}
            $table->timestamp('terminated_at')->nullable();
            $table->timestamps();
            
            $table->foreign('resource_id')->references('id')->on('compute_resources')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['resource_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_rentals');
    }
};