<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'slug', 'short_description', 'full_description',
        'keywords', 'demo_available', 'demo_url', 'icon', 'display_order',
        'is_featured', 'is_active', 'price', 'price_display'
    ];

    protected $casts = [
        'keywords' => 'array',
        'demo_available' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2'
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
