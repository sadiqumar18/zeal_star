<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DataProduct extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ussd_param' => 'array', // Will convarted to (array)  
    ];



}
