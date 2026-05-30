<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'short_description',
        'full_description',
        'keywords',
        'enquiry_available',
        'icon',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'services'  => 'array',
        'keywords' => 'array',
        'enquiry_available' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function matchesKeywords($message)
    {
        $messageLower = strtolower($message);

        foreach ($this->keywords as $keyword) {
            if (stripos($messageLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
