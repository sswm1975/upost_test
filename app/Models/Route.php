<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Route
 *
 * @property int $id Код
 * @property int $parent_id Код родителя
 * @property int $user_id Код пользователя
 * @property int|null $from_country_id Код страны старта
 * @property int|null $from_city_id Код города старта
 * @property int|null $to_country_id Код страны окончания
 * @property int|null $to_city_id Код города окончания
 * @property int $looks Количество просмотров
 * @property string|null $fromdate Дата начала маршрута
 * @property string|null $tilldate Дата окончания маршрута
 * @property string|null $transport Вид транспорта
 * @property string $type Тип маршрута
 * @property string $status Статус маршрута
 * @property string $register_date Дата регистрации маршрута
 * @property-read \App\Models\City|null $from_city
 * @property-read \App\Models\Country|null $from_country
 * @property-read bool $is_favorite
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rate[] $rates
 * @property-read int|null $rates_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RoutePoint[] $route_points
 * @property-read int|null $route_points_count
 * @property-read \App\Models\City|null $to_city
 * @property-read \App\Models\Country|null $to_country
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCityInFromCity(array $cities)
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCityInToCity(array $cities)
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCountryInFromCountry(array $countries)
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCountryInToCountry(array $countries)
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCountryOrCity(string $routes_field, string $route_points_field, array $rows)
 * @method static \Illuminate\Database\Eloquent\Builder|Route newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Route newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Route query()
 * @method static \Illuminate\Database\Eloquent\Builder|Route successful()
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereFromCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereFromCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereFromdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereLooks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereRegisterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereTilldate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereToCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereToCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereTransport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereType($value)
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
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $appends = [
        'is_favorite',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->id;
            $model->parent_id = 0;
            $model->type = 'route';
            $model->status = self::STATUS_ACTIVE;
            $model->looks = 0;
            $model->register_date = Date::now();
        });
    }

    public function getIsFavoriteAttribute(): bool
    {
        $user = request()->user();

        if (empty($user->favorite_routes)) {
            return false;
        }

        return in_array($this->id, explode(',', $user->favorite_routes));
    }

    public function setFromCityIdAttribute($value)
    {
        $this->attributes['from_city_id'] = is_null($value) ? 0 : $value;
    }

    public function setToCityIdAttribute($value)
    {
        $this->attributes['to_city_id'] = is_null($value) ? 0 : $value;
    }

    ### LINKS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function from_country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'from_country_id', 'id')
            ->select(['id', "name_{$lang} as name"])
            ->withDefault();
    }

    public function from_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'from_city_id', 'id')
            ->select(['id', "name_{$lang} as city_name"])
            ->withDefault();
    }

    public function to_country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'to_country_id', 'id')
            ->select(['id', "name_{$lang} as name"])
            ->withDefault();
    }

    public function to_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'to_city_id', 'id')
            ->select(['id', "name_{$lang} as name"])
            ->withDefault();
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'route_id', 'id');
    }

    public function route_points(): HasMany
    {
        return $this->hasMany(RoutePoint::class, 'route_id', 'id');
    }

    ### SCOPES ###

    public function scopeSuccessful($query)
    {
        return $query->whereStatus(self::STATUS_SUCCESSFUL);
    }

    /**
     * Универсальный скоуп для поиска страны или города в поле начала маршрута или окончания маршрута, а также в смежных маршрутах.
     *
     * @param $query
     * @param string $routes_field        - одно из полей таблицы route: from_country_id, to_country_id
     * @param string $route_points_field  - одно из полей таблицы route_points: country_id, city_id
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
                        ->whereRaw('route_points.route_id = routes.id')
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
        return $query->existsCountryOrCity('from_country_id', 'country_id', $countries);
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
        return $query->existsCountryOrCity('to_country_id', 'country_id', $countries);
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
        return $query->existsCountryOrCity('from_city_id', 'city_id', $cities);
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
        return $query->existsCountryOrCity('to_city_id', 'city_id', $cities);
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

        if (empty($user->favorite_routes)) {
            return [];
        }

        return static::whereIn('id', explode(',', $user->favorite_routes))
            ->with([
                'user' => function ($query) {
                    $query->select([
                        'id',
                        'name',
                        'surname',
                        'creator_rating',
                        'freelancer_rating',
                        'photo',
                        'favorite_orders',
                        'favorite_routes',
                        DB::raw('(select count(*) from `orders` where `users`.`id` = `orders`.`user_id` and `status` = "successful") as successful_orders')
                    ]);
                },
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount(['rates' => function ($query) use ($user) {
                $query->whereParentId(0)->whereUserId($user->id);
            }])
            ->get();
    }
}
