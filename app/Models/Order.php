<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * App\Models\Order
 *
 * @property int $id Код
 * @property int $user_id Код пользователя
 * @property string $name Наименование
 * @property string|null $slug Слаг
 * @property string|null $product_link Ссылка на товар в интернет магазине
 * @property string|null $shop_slug Слаг магазина
 * @property string $price Цена
 * @property string|null $currency Валюта
 * @property string $price_usd Цена в долларах
 * @property int|null $products_count Количество товаров
 * @property string|null $description Описание заказа
 * @property array $images Фотографии заказа
 * @property int|null $from_country_id Код страны начала заказа
 * @property int|null $from_city_id Код города начала заказа
 * @property int|null $to_country_id Код страны окончания заказа
 * @property int|null $to_city_id Код города окончания заказа
 * @property string $register_date Дата регистрации
 * @property string|null $deadline Дата окончания заказа
 * @property int|null $wait_range_id Код диапазона ожидания
 * @property string $user_price Сумма дохода
 * @property string|null $user_currency Валюта дохода
 * @property string $user_price_usd Сумма дохода в долларах
 * @property int $not_more_price Признак "Не принимать ставки выше данной цены"
 * @property int $is_user_active Признак активности пользователя
 * @property int $looks Количество просмотров
 * @property string $status Статус заказа
 * @property array|null $strikes Жалобы
 * @property string|null $created_at Добавлено
 * @property string|null $updated_at Изменено
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderCalculation[] $calculations
 * @property-read int|null $calculations_count
 * @property-read \App\Models\City|null $from_city
 * @property-read \App\Models\Country|null $from_country
 * @property-read array $images_medium
 * @property-read array $images_original
 * @property-read array $images_thumb
 * @property-read string $short_name
 * @property-read string $status_name
 * @property-read string $total_amount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rate[] $rates
 * @property-read int|null $rates_count
 * @property-read \App\Models\Review|null $review
 * @property-read \App\Models\City|null $to_city
 * @property-read \App\Models\Country|null $to_country
 * @property-read \App\Models\User $user
 * @property-read \App\Models\WaitRange|null $wait_range
 * @method static \Illuminate\Database\Eloquent\Builder|Order active()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order owner()
 * @method static \Illuminate\Database\Eloquent\Builder|Order ownerWithStatuses(array $statuses = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order searchByRoutes(bool $only_new = false, array $statuses = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Order successful()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereFromCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereFromCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereIsUserActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereLooks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereNotMorePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePriceUsd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereProductLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereProductsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereRegisterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShopSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStrikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereToCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereToCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserPriceUsd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereWaitRangeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order withoutAppends()
 * @mixin \Eloquent
 */
class Order extends Model
{
    use TimestampSerializable;

    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = [
        'images' => 'array',
        'strikes' => 'array',
    ];
    protected $appends = [
        'total_amount',
        'short_name',
        'status_name',
        'images_thumb',
        'images_medium',
        'images_original',
    ];

    /**
     * Флаг, что в модель не нужно добавлять $appends атрибуты (исп. при выгрузке в эксель из админки)
     *
     * @var bool
     */
    public static bool $withoutAppends = false;

    const STATUS_ACTIVE = 'active';
    const STATUS_IN_WORK = 'in_work';
    const STATUS_SUCCESSFUL = 'successful';
    const STATUS_CLOSED = 'closed';
    const STATUS_FAILED = 'failed';
    const STATUS_BANNED = 'banned';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_IN_WORK,
        self::STATUS_SUCCESSFUL,
        self::STATUS_CLOSED,
        self::STATUS_FAILED,
        self::STATUS_BANNED,
    ];

    ### GETTERS ###

    public function getTotalAmountAttribute(): string
    {
        return $this->price * $this->products_count;
    }

    public function getShortNameAttribute(): string
    {
        return Str::limit($this->name, 10, '…');
    }

    public function getStatusNameAttribute(): string
    {
        return __("message.order.statuses.$this->status");
    }

    public function getImagesAttribute($images): array
    {
        if (is_null($images)) return [];

        if (is_string($images)) {
            $images = json_decode($images);
        }

        $link_images = [];
        foreach ($images as $image) {
            $link_images[] = asset("storage/{$this->user_id}/orders/{$image}");
        }

        return $link_images;
    }

    public function getImagesThumbAttribute(): array
    {
        if (is_null($this->images)) return [];

        $images = [];
        foreach ($this->images as $image) {
            $images[] = str_replace('image_', 'image_thumb_', $image);
        }

        return $images;
    }

    public function getImagesMediumAttribute(): array
    {
        if (is_null($this->images)) return [];

        $images = [];
        foreach ($this->images as $image) {
            $images[] = str_replace('image_', 'image_medium_', $image);
        }

        return $images;
    }

    public function getImagesOriginalAttribute(): array
    {
        if (is_null($this->images)) return [];

        $images = [];
        foreach ($this->images as $image) {
            $images[] = str_replace('image_', 'image_original_', $image);
        }

        return $images;
    }

    public function getStrikesAttribute($json)
    {
        if (is_null($json)) return [];

        if (is_array($json)) return $json;

        return json_decode($json, true);
    }

    ### SETTERS ###

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = !empty($value) ? strip_tags(strip_unsafe($value)) : null;
    }

    public function setProductLinkAttribute($value)
    {
        $product_link = $this->attributes['product_link'] ?? null;
        if ($product_link == $value) return;

        $this->attributes['product_link'] = $value;

        $shop_slug = null;
        $host = strtolower(parse_url($value, PHP_URL_HOST));
        $slugs = Shop::pluck('slug')->toArray();
        foreach ($slugs as $slug) {
            if (Str::contains($host, $slug)) {
                $shop_slug = $slug;
            }
        }
        $this->attributes['shop_slug'] = $shop_slug;
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = !empty($value)
            ? strip_tags(strip_unsafe($value), ['p', 'span', 'b', 'i', 's', 'u', 'strong', 'italic', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])
            : null;
    }

    public function setImagesAttribute($images)
    {
        if (empty($images)) {
            $this->attributes['images'] = null;
        }

        foreach ($images as $key => $image) {
            $uri_parts = explode('/', $image);
            $images[$key] = end($uri_parts);
        }

        $this->attributes['images'] = json_encode($images);
    }

    public function setWaitRangeIdAttribute($value)
    {
        if (isset($this->attributes['wait_range_id'])) {
            if ($this->attributes['wait_range_id'] == $value) return;
            $register_date = Date::createFromFormat( 'Y-m-d', $this->attributes['register_date']);
        } else {
            $register_date = Date::now();
        }

        $this->attributes['wait_range_id'] = $value;
        $wait_days = WaitRange::find($value)->days;
        $this->attributes['deadline'] = $register_date->addDays($wait_days)->format('Y-m-d');
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

    public function wait_range(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(WaitRange::class, 'wait_range_id', 'id')
            ->select(['id', "name_{$lang} as name"])
            ->withDefault();
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'order_id', 'id');
    }

    public function review(): MorphOne
    {
        return $this->morphOne(Review::class, 'reviewable');
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(OrderCalculation::class, 'order_id', 'id');
    }

    ### SCOPES ###

    public function scopeOwner($query)
    {
        return $query->where('user_id', request()->user()->id);
    }

    public function scopeOwnerWithStatuses($query, array $statuses = [])
    {
        return $query->owner()
            ->when(!empty($statuses), function ($query) use ($statuses) {
                return $query->whereIn('status', $statuses);
            });
    }

    function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereStatus(self::STATUS_SUCCESSFUL);
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
     * Поиск заказов по данным маршрута/ов.
     *
     * @param $query
     * @param bool $only_new - флаг "Только новые заказы"
     * @param array $statuses - список статусов заказа
     * @return mixed
     */
    public function scopeSearchByRoutes($query, bool $only_new = false, array $statuses = [self::STATUS_ACTIVE])
    {
        return $query->whereIn('orders.status', $statuses)
            ->whereBetweenColumns('routes.deadline', ['orders.register_date', 'orders.deadline'])
            ->whereColumn('orders.from_country_id', 'routes.from_country_id')
            ->whereColumn('orders.to_country_id', 'routes.to_country_id')
            ->where(function($query) {
                return $query->whereRaw('IFNULL(orders.from_city_id, 0) = IFNULL(routes.from_city_id, 0)')
                    ->orWhere(function ($query) {
                        return $query->whereNull('orders.from_city_id')->where('routes.from_city_id', '>', 0);
                    })
                    ->orWhere(function ($query) {
                        return $query->whereNull('routes.from_city_id')->where('orders.from_city_id', '>', 0);
                    });
            })
            ->where(function($query) {
                return $query->whereRaw('IFNULL(orders.to_city_id, 0) = IFNULL(routes.to_city_id, 0)')
                    ->orWhere(function ($query) {
                        return $query->whereNull('orders.to_city_id')->where('routes.to_city_id', '>', 0);
                    })
                    ->orWhere(function ($query) {
                        return $query->whereNull('routes.to_city_id')->where('orders.to_city_id', '>', 0);
                    });
            })
            ->when($only_new, function ($query) {
                return $query->where('orders.created_at', '>', DB::Raw('IFNULL(routes.viewed_orders_at, "1900-01-01 00:00:00")'));
            });
    }

    /**
     * Скоуп: В модель не добавлять доп.атрибуты массива $appends.
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
}
