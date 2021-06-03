<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country';
    protected $primaryKey = 'country_id';
    protected $fillable  = ['country_name_uk', 'country_name_ru', 'country_name_en'];
    public $timestamps = false;

    public function scopeLanguage($query, $lang = 'en')
    {
        return $query->select('country_name_' . $lang . ' as country_name');
    }
}
