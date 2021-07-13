<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['response' => 'array'];
    protected $dates = ['payed_at'];
}
