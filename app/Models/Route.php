<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use App\Models\Traits\WithoutAppends;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Route
 *
 * @property int $id Код
 * @property int $user_id Код пользователя
 * @property string $from_country_id Код страны старта
 * @property int|null $from_city_id Код города старта
 * @property string $to_country_id Код страны окончания
 * @property int|null $to_city_id Код города окончания
 * @property string|null $deadline Дата окончания маршрута
 * @property string $status Статус маршрута
 * @property \Illuminate\Support\Carbon $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 * @property \Illuminate\Support\Carbon|null $viewed_orders_at Дата просмотра заказов по маршруту
 * @property-read \App\Models\City|null $from_city
 * @property-read \App\Models\Country $from_country
 * @property-read string $status_name
 * @property-read \App\Models\Order|null $order
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rate[] $rates
 * @property-read int|null $rates_count
 * @property-read \App\Models\Review|null $review
 * @property-read \App\Models\City|null $to_city
 * @property-read \App\Models\Country $to_country
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Route active()
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCityInFromCity(array $cities)
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCityInToCity(array $cities)
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCountryInFromCountry(array $countries)
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCountryInToCountry(array $countries)
 * @method static \Illuminate\Database\Eloquent\Builder|Route existsCountryOrCity(string $routes_field, array $rows)
 * @method static \Illuminate\Database\Eloquent\Builder|Route filterByType($type)
 * @method static \Illuminate\Database\Eloquent\Builder|Route newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Route newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Route owner()
 * @method static \Illuminate\Database\Eloquent\Builder|Route query()
 * @method static \Illuminate\Database\Eloquent\Builder|Route successful()
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereFromCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereFromCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereToCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereToCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereViewedOrdersAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route withoutAppends(array $appends = [])
 * @mixin \Eloquent
 */
class Route extends Model
{
    use TimestampSerializable;
    use WithoutAppends;

    const STATUS_ACTIVE     = 'active';
    const STATUS_SUCCESSFUL = 'successful';
    const STATUS_CLOSED     = 'closed';
    const STATUS_BANNED     = 'banned';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUCCESSFUL,
        self::STATUS_CLOSED,
        self::STATUS_BANNED,
    ];

    /**
     * Типы фильтров на странице Мои маршруты: Активные, Завершенные
     */
    const FILTER_TYPE_ACTIVE = 'active';
    const FILTER_TYPE_COMPLETED = 'completed';
    const FILTER_TYPES = [
        self::FILTER_TYPE_ACTIVE,
        self::FILTER_TYPE_COMPLETED,
    ];

    protected $table = 'routes';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $dates = ['viewed_orders_at'];
    protected $appends = ['status_name'];

    ### GETTERS ###

    public function getStatusNameAttribute(): string
    {
        return __("message.route.statuses.$this->status");
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
            ->select(['id', 'name_en', "name_{$lang} as name"])
            ->withDefault();
    }

    public function from_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'from_city_id', 'id')
            ->select(['id', 'name_en', "name_{$lang} as name"])
            ->withDefault();
    }

    public function to_country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'to_country_id', 'id')
            ->select(['id', 'name_en', "name_{$lang} as name"])
            ->withDefault();
    }

    public function to_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'to_city_id', 'id')
            ->select(['id', 'name_en', "name_{$lang} as name"])
            ->withDefault();
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'route_id', 'id');
    }

    function order(): HasOneThrough
    {
        return $this->hasOneThrough(Order::class, Rate::class, 'route_id', 'id', 'id', 'order_id');
    }

    function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, Rate::class, 'route_id', 'id', 'id', 'order_id');
    }

    public function review(): MorphOne
    {
        return $this->morphOne(Review::class, 'reviewable');
    }

    ### SCOPES ###

    public function scopeOwner($query)
    {
        return $query->where('user_id', request()->user()->id);
    }

    public function scopeActive($query)
    {
        return $query->whereStatus(self::STATUS_ACTIVE);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereStatus(self::STATUS_SUCCESSFUL);
    }

    /**
     * Отобрать маршруты по типу статусов.
     *
     * @param $query
     * @param $type
     * @return mixed
     */
    public function scopeFilterByType($query, $type)
    {
        switch ($type) {
            case self::FILTER_TYPE_ACTIVE:
                $query->active();
                break;
            case self::FILTER_TYPE_COMPLETED:
                $query->whereIn('status', [self::STATUS_CLOSED, self::STATUS_SUCCESSFUL, self::STATUS_BANNED]);
                break;
        }

        return $query;
    }

    /**
     * Универсальный скоуп для поиска страны или города в поле начала маршрута или окончания маршрута, а также в смежных маршрутах.
     *
     * @param $query
     * @param string $routes_field        - одно из полей таблицы route: from_country_id, to_country_id
     * @param array $rows                 - массив кодов стран или городов, например [1,4,78].
     * @return mixed
     */
    public function scopeExistsCountryOrCity($query, string $routes_field, array $rows)
    {
        return $query->whereIn($routes_field, $rows);
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
     * Получить маршрут/ы владельца по списку ключей и выбранным статусам.
     *
     * @param $query
     * @param mixed $id
     * @param array $statuses
     * @return mixed
     */
    protected function scopeIsOwnerByKey($query, $id, array $statuses = [self::STATUS_ACTIVE])
    {
        return $query->owner()->whereKey($id)->whereIn('status', $statuses);
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
     * Возвращает маршрут по его коду вместе со всеми отношениями.
     *
     * @param int $id
     * @return Route|null
     */
    public static function getByIdWithRelations(int $id): ?Route
    {
        return static::whereKey($id)
            ->with(['from_country', 'from_city', 'to_country', 'to_city'])
            ->withCount(['order as budget_usd' => function($query) {
                $query
                    ->whereNotIn('orders.status', [Order::STATUS_ACTIVE, Order::STATUS_FAILED, Order::STATUS_BANNED])
                    ->select(DB::raw('IFNULL(SUM(orders.price_usd * orders.products_count + orders.deduction_usd), 0)'));
            }])
            ->withCount(['order as profit_usd' => function($query) {
                $query
                    ->whereNotIn('orders.status', [Order::STATUS_ACTIVE, Order::STATUS_FAILED, Order::STATUS_BANNED])
                    ->select(DB::raw('IFNULL(SUM(orders.user_price_usd), 0)'));
            }])
            ->first();
    }
}
