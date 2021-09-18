<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
    const STATUS_SUCCESSFUL = 'successful';

    protected $table = 'routes';
    protected $primaryKey = 'route_id';
    protected $guarded = ['route_id'];
    public $timestamps = false;
    protected $casts = [
        'route_points' => 'object',
    ];
    protected $appends = [
        'route_is_favorite',
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

    public function getRouteIsFavoriteAttribute(): bool
    {
        $user = request()->user();

        if (empty($user->user_favorite_routes)) {
            return false;
        }

        return in_array($this->route_id, explode(',', $user->user_favorite_routes));
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

    ### LINKS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
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

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'route_id', 'route_id');
    }

    public function route_points(): HasMany
    {
        return $this->hasMany(RoutePoint::class, 'route_id', 'route_id');
    }

    ### SCOPES ###

    public function scopeSuccessful($query)
    {
        return $query->where('route_status', self::STATUS_SUCCESSFUL);
    }

    /**
     * Универсальный скоуп для поиска страны или города в поле начала маршрута или окончания маршрута, а также в смежных маршрутах.
     *
     * @param $query
     * @param string $routes_field        - одно из полей таблицы route: route_from_country, route_to_country
     * @param string $route_points_field  - одно из полей таблицы route_points: country, city
     * @param array $rows                 - массив кодов стран или городов, например [1,4,78].
     * @return mixed
     */
    public function scopeExistsCountryOrCity($query, string $routes_field, string $route_points_field, array $rows)
    {
        return $query->where(function ($query) use ($routes_field, $route_points_field, $rows) {
            return $query->whereIn($routes_field, $rows)
                ->orWhereExists(function($query) use ($route_points_field, $rows) {
                    $query->selectRaw(1)
                        ->from('route_points')
                        ->whereRaw('route_points.route_id = routes.route_id')
                        ->whereIn($route_points_field, $rows);
                });
        });
    }

    /**
     * Существует страна/ы в начальном маршруте или смежных маршрутах.
     *
     * @param $query
     * @param array $countries - массив кодов стран, например [1,4,78].
     * @return mixed
     */
    public function scopeExistsCountryInFromCountry($query, array $countries)
    {
        return $query->existsCountryOrCity('route_from_country', 'country', $countries);
    }

    /**
     * Существует страна/ы в конечном маршруте или смежных маршрутах.
     *
     * @param $query
     * @param array $countries - массив кодов стран, например [1,4,78].
     * @return mixed
     */
    public function scopeExistsCountryInToCountry($query, array $countries)
    {
        return $query->existsCountryOrCity('route_to_country', 'country', $countries);
    }

    /**
     * Существует город/а в начальном маршруте или смежных маршрутах.
     *
     * @param $query
     * @param array $cities - массив кодов городов, например [1,4,78].
     * @return mixed
     */
    public function scopeExistsCityInFromCity($query, array $cities)
    {
        return $query->existsCountryOrCity('route_from_city', 'city', $cities);
    }

    /**
     * Существует город/а в конечном маршруте или смежных маршрутах.
     *
     * @param $query
     * @param array $cities - массив кодов городов, например [1,4,78].
     * @return mixed
     */
    public function scopeExistsCityInToCity($query, array $cities)
    {
        return $query->existsCountryOrCity('route_to_city', 'city', $cities);
    }

    ### QUERIES ###

    /**
     * Получить список избранных маршрутов авторизированного пользователя.
     *
     * @return array|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getFavorites()
    {
        $user = request()->user();

        if (empty($user->user_favorite_routes)) {
            return [];
        }

        return static::whereIn('route_id', explode(',', $user->user_favorite_routes))
            ->with([
                'user' => function ($query) {
                    $query->select([
                        'user_id',
                        'user_name',
                        'user_surname',
                        'user_creator_rating',
                        'user_freelancer_rating',
                        'user_photo',
                        'user_favorite_orders',
                        'user_favorite_routes',
                        DB::raw('(select count(*) from `orders` where `users`.`user_id` = `orders`.`user_id` and `order_status` = "successful") as user_successful_orders')
                    ]);
                },
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount(['rates' => function ($query) use ($user) {
                $query->where('parent_id', 0)->where('user_id', $user->user_id);
            }])
            ->get();
    }
}
