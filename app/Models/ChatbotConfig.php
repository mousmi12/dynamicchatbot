<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotConfig extends Model
{
     protected $fillable = [
        'company_id', 'config_key', 'config_value', 'category'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
