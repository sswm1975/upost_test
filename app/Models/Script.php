<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

class Script extends Model
{
    use TimestampSerializable;

    public function from_countries()
    {
        return $this->belongsToMany(Country::class, 'script_from_country');
    }

    public function to_countries()
    {
        return $this->belongsToMany(Country::class, 'script_to_country');
    }
}
