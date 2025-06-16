<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filter_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('description');
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable(); // Additional filter configuration
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filter_settings');
    }
};