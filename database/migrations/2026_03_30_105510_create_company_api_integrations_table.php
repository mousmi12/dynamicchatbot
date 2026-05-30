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
        Schema::create('company_api_integrations', function (Blueprint $table) {
            $table->id();
           $table->unsignedBigInteger('company_id');
            $table->string('integration_key', 50);
            $table->string('auth_base_url');
            $table->string('auth_endpoint');
            $table->string('token_path')->default('token');
            $table->string('auth_identifier_field')->default('email');
            $table->integer('token_ttl')->default(3600);
            $table->text('services')->nullable();
            $table->tinyInteger('is_active')->default(1);          
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'integration_key']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_api_integrations');
    }
};
