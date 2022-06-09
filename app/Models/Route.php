<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

class Route extends Model
{
    use TimestampSerializable;

    const STATUS_ALL        = 'all';
    const STATUS_ACTIVE     = 'active';
    const STATUS_IN_WORK    = 'in_work';
    const STATUS_SUCCESSFUL = 'successful';
    const STATUS_CLOSED     = 'closed';
    const STATUS_BANNED     = 'banned';

    const STATUSES = [
        self::STATUS_ALL,
        self::STATUS_ACTIVE,
        self::STATUS_IN_WORK,
        self::STATUS_SUCCESSFUL,
        self::STATUS_CLOSED,
        self::STATUS_BANNED,
    ];

    protected $table = 'routes';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $appends = [
        'status_name',
    ];

    /**
     * Флаг, что в модель не нужно добавлять $appends атрибуты (исп. при выгрузке в эксель из админки)
     *
     * @var bool
     */
    public static bool $withoutAppends = false;

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
            ->select(['id', "name_{$lang} as name"])
            ->withDefault();
    }

    public function from_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'from_city_id', 'id')
            ->select(['id', "name_{$lang} as name"])
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

    function order(): HasOneThrough
    {
        return $this->hasOneThrough(Order::class, Rate::class, 'route_id', 'id', 'id', 'order_id');
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

    public function scopeSuccessful($query)
    {
        return $query->whereStatus(self::STATUS_SUCCESSFUL);
    }

    public function scopeFilterByStatus($query, $status)
    {
        if ($status == self::STATUS_ALL) {
            return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_IN_WORK]);
        }
        if ($status == self::STATUS_CLOSED) {
            return $query->whereIn('status', [self::STATUS_CLOSED, self::STATUS_SUCCESSFUL, self::STATUS_BANNED]);
        }
        return $query->where('status', $status);
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

    /**
     * Скоуп: В модель не добавлять доп.атрибутиты массива $appends.
     *
     * @param $query
     * @return mixed
     */
    public function scopeWithoutAppends($query)
    {
        self::$withoutAppends = true;

        return $query;
    }

    /**
     * Get all of the appendable values that are arrayable.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (self::$withoutAppends){
            return [];
        }

        return parent::getArrayableAppends();
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
                $query->select(DB::raw('IFNULL(SUM(orders.price_usd), 0)'));
            }])
            ->withCount(['order as profit_usd' => function($query) {
                $query->select(DB::raw('IFNULL(SUM(orders.user_price_usd), 0)'));
            }])
            ->first();
    }
}
