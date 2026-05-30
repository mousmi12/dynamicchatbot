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
        Schema::create('api_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_endpoint_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->string('sync_type'); // manual, auto, scheduled
            $table->string('status'); // success, failed, partial
            $table->integer('records_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->text('request_params')->nullable(); // Changed from json to text
            $table->text('response_summary')->nullable(); // Changed from json to text
            
            $table->timestamp('started_at')->nullable(); // Made nullable
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['company_id', 'sync_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_sync_logs');
    }
};
