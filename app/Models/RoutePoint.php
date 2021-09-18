<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutePoint extends Model
{
    protected $fillable = ['country', 'city', 'date'];
    public $timestamps = false;

    ### LINKS ###

    public function country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'country', 'country_id')
            ->select(['country_id', "country_name_{$lang} as country_name"])
            ->withDefault();
    }

    public function city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'city', 'city_id')
            ->select(['city_id', "city_name_{$lang} as city_name"])
            ->withDefault();
    }
}
