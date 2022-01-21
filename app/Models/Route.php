<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use DateTimeInterface;

class Route extends Model
{
    const STATUS_ALL        = 'all';
    const STATUS_ACTIVE     = 'active';
    const STATUS_IN_WORK    = 'in_work';
    const STATUS_SUCCESSFUL = 'successful';
    const STATUS_CLOSED     = 'closed';
    const STATUS_BAN        = 'ban';

    const STATUSES = [
        self::STATUS_ALL,
        self::STATUS_ACTIVE,
        self::STATUS_IN_WORK,
        self::STATUS_SUCCESSFUL,
        self::STATUS_CLOSED,
        self::STATUS_BAN,
    ];

    protected $table = 'routes';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $appends = [
        'status_name',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     * https://laravel.com/docs/7.x/upgrade#date-serialization
     *
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

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
            return $query->whereIn('status', [self::STATUS_CLOSED, self::STATUS_SUCCESSFUL, self::STATUS_BAN]);
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

    /**
     * Возвращает маршрут по его коду вместе со всеми отношениями.
     *
     * @param int $id
     * @return array
     */
    protected static function getByIdWithRelations(int $id): array
    {
        return static::with(['from_country', 'from_city', 'to_country', 'to_city'])
            ->find($id)
            ->toArray();
    }
}
