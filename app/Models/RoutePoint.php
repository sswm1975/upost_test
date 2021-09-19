<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\RoutePoint
 *
 * @property int $id Код
 * @property int $route_id Код маршрута
 * @property int $country_id Код страны
 * @property int $city_id Код города
 * @property string $date Дата нахождения
 * @property-read \App\Models\City $city
 * @property-read \App\Models\Country $country
 * @method static \Illuminate\Database\Eloquent\Builder|RoutePoint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoutePoint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoutePoint query()
 * @method static \Illuminate\Database\Eloquent\Builder|RoutePoint whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoutePoint whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoutePoint whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoutePoint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoutePoint whereRouteId($value)
 * @mixin \Eloquent
 */
class RoutePoint extends Model
{
    protected $fillable = ['country_id', 'city_id', 'date'];
    public $timestamps = false;

    ### LINKS ###

    public function country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'country_id', 'id')
            ->select(['id', "name_{$lang} as name"])
            ->withDefault();
    }

    public function city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'city_id', 'id')
            ->select(['id', "name_{$lang} as name", 'country_id'])
            ->withDefault();
    }
}
