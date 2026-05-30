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
        Schema::create('api_connections', function (Blueprint $table) {


            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->string('connection_name');
            $table->string('api_type')->default('rest');
            $table->string('base_url');
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->text('headers')->nullable();
            $table->text('auth_config')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_connections');
    }
};
