<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'country';
    protected $primaryKey = 'country_id';
    protected $fillable  = ['country_name_uk', 'country_name_ru', 'country_name_en'];
    public $timestamps = false;

    /**
     * @return HasMany
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_id');
    }

    /**
     * Scope a query for selecting the column name depending on the specified language.
     *
     * @param Builder $query
     * @param string $lang
     * @return Builder
     */
    public function scopeLanguage(Builder $query, string $lang = 'en')
    {
        return $query->select('country_name_' . $lang . ' as country_name');
    }
}
