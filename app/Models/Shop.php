<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use TimestampSerializable;

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = strtolower($value);
    }
}
