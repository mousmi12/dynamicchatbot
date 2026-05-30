<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_connection_id');
            
            $table->string('endpoint_name'); // e.g., "Get Medicines", "Get Orders"
            $table->string('endpoint_path'); // e.g., "/api/medicines"
            $table->string('method')->default('GET'); // GET, POST, PUT, DELETE
            $table->text('query_params')->nullable(); // Changed from json to text
            $table->text('request_body')->nullable(); // Changed from json to text
            
            $table->string('cache_key_prefix')->nullable();
            $table->integer('cache_duration')->default(3600); // seconds
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Add foreign key constraint separately
            $table->foreign('api_connection_id')
                  ->references('id')
                  ->on('api_connections')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
    }
};