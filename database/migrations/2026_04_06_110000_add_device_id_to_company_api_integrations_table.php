<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add device_id column to company_api_integrations.
     *
     * Danabook's login API requires a device_id field.
     * Store it here so each integration can have a fixed device ID
     * configured by the admin (no need to ask users for it).
     */
    public function up(): void
    {
        Schema::table('company_api_integrations', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('auth_identifier_field');
        });
    }

    public function down(): void
    {
        Schema::table('company_api_integrations', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
};
