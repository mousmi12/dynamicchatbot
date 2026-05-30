<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceEnquiry extends Model
{
     protected $table = 'service_enquiries'; 
     protected $fillable = ['service','name','mobile','requirement'];
}