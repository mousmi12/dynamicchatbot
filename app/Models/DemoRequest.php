<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
     protected $fillable = [
        'customer_name',
        'company_name',
        'mobile',
        'email',
        'product'
    ];
}
