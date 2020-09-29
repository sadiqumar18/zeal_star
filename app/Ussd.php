<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Ussd extends Model
{
    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
