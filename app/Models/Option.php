<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $table = 'options';
    protected $primaryKey = 'option_id';
    protected $guarded = ['option_id'];
    public $timestamps = false;

    public function scopeRate($query, $currency = 'usd')
    {
        return $query->where('option_name',  $currency)->first()->option_val ?? 1;
    }
}
