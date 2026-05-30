<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add extra fields required by Danabook's login API:
     *  - extra_auth_fields : JSON – stores orgid, devicename, fingerprint, encrypt, etc.
     *  - password_algo     : string – if set (e.g. 'sha256'), the password is hashed before sending
     */
    public function up(): void
    {
        Schema::table('company_api_integrations', function (Blueprint $table) {
            $table->text('extra_auth_fields')->nullable();
            $table->string('password_algo', 20)->nullable(); // e.g. 'sha256'
        });
    }

    public function down(): void
    {
        Schema::table('company_api_integrations', function (Blueprint $table) {
            $table->dropColumn(['extra_auth_fields', 'password_algo']);
        });
    }
};
