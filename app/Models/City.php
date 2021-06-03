<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'city';
    protected $primaryKey = 'city_id';
    protected $fillable  = ['city_name_uk', 'city_name_ru', 'city_name_en'];
    public $timestamps = false;

    public function scopeLanguage($query, $lang = 'en')
    {
        return $query->select('city_name_' . $lang . ' as city_name');
    }
}
