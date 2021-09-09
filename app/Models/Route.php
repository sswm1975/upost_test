<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Route
 *
 * @property int $route_id Код
 * @property int $route_parent Код родителя
 * @property int $user_id Код пользователя
 * @property int|null $route_from_country Код страны старта
 * @property int|null $route_from_city Код города старта
 * @property int|null $route_to_country Код страны окончания
 * @property int|null $route_to_city Код города окончания
 * @property int $route_look Количество просмотров
 * @property string|null $route_register_date Дата регистрации маршрута
 * @property string|null $route_start Дата начала маршрута
 * @property string|null $route_end Дата окончания маршрута
 * @property string|null $route_transport Вид транспорта
 * @property string $route_type Тип маршрута
 * @property string $route_status Статус маршрута
 * @property object|null $route_points Смежные точки маршрута
 * @method static \Illuminate\Database\Eloquent\Builder|Route newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Route newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Route query()
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteFromCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteFromCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteLook($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteParent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRoutePoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteRegisterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteToCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteToCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteTransport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRouteType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereUserId($value)
 * @mixin \Eloquent
 */
class Route extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_BAN = 'ban';

    protected $table = 'routes';
    protected $primaryKey = 'route_id';
    protected $guarded = ['route_id'];
    public $timestamps = false;
    protected $casts = [
        'route_points' => 'object',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->user_id;
            $model->route_parent = 0;
            $model->route_type = 'route';
            $model->route_status = self::STATUS_ACTIVE;
            $model->route_look = 0;
            $model->route_register_date = date('Y-m-d');
        });
    }

    public function setRouteFromCityAttribute($value)
    {
        $this->attributes['route_from_city'] = is_null($value) ? 0 : $value;
    }

    public function setRouteToCityAttribute($value)
    {
        $this->attributes['route_to_city'] = is_null($value) ? 0 : $value;
    }

    public function getRoutePointsAttribute($json)
    {
        if (is_null($json)) return [];

        if (is_array($json)) return $json;

        return json_decode($json, true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function from_country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'route_from_country', 'country_id')
            ->select(['country_id', "country_name_{$lang} as country_name"])
            ->withDefault();
    }

    public function from_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'route_from_city', 'city_id')
            ->select(['city_id', "city_name_{$lang} as city_name"])
            ->withDefault();
    }

    public function to_country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'route_to_country', 'country_id')
            ->select(['country_id', "country_name_{$lang} as country_name"])
            ->withDefault();
    }

    public function to_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'route_to_city', 'city_id')
            ->select(['city_id', "city_name_{$lang} as city_name"])
            ->withDefault();
    }
}
