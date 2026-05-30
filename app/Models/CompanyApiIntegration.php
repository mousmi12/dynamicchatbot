<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyApiIntegration extends Model
{
  protected $fillable = [
        'company_id',
        'integration_key',
        'auth_base_url',
        'auth_endpoint',
        'token_path',
        'auth_identifier_field',
        'orgid',
        'device_id',
        'token_ttl',
        'password_algo',
        'password_salt',
        'extra_auth_fields',
        'services',
        'is_active',
    ];
 
    protected $casts = [
         'services'          => 'array',
        'extra_auth_fields' => 'array',
        'is_active'         => 'boolean',
    ];
 
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
