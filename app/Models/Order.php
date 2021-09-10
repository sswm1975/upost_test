<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * App\Models\Order
 *
 * @property int $order_id Код
 * @property int $user_id Код пользователя
 * @property string $order_register_date Дата регистрации
 * @property string|null $order_name Наименование
 * @property string|null $order_url Слаг
 * @property int|null $order_category Код категории
 * @property string $order_price Цена
 * @property string $order_price_usd Цена в долларах
 * @property string|null $order_currency Валюта
 * @property int|null $order_count Количество товаров
 * @property string|null $order_size Размер товара
 * @property string|null $order_weight Вес товара
 * @property string|null $order_product_link Ссылка на товар на чужом сайте
 * @property string|null $order_text Описание заказа
 * @property array|null $order_images Фотографии заказа
 * @property int|null $order_from_country Код страны начала заказа
 * @property int|null $order_from_city Код города начала заказа
 * @property string|null $order_from_address Точный адрес старта заказа
 * @property int|null $order_to_country Код страны окончания заказа
 * @property int|null $order_to_city Код города окончания заказа
 * @property string|null $order_to_address Точный адрес прибытия заказа
 * @property string|null $order_start Дата начала заказа
 * @property string|null $order_deadline Дата окончания заказа
 * @property int $order_personal_price Признак персонального вознаграждения
 * @property string $order_user_price Сумма персонального вознаграждения
 * @property string|null $order_user_currency Валюта персонального вознаграждения
 * @property int $order_not_more_price Признак
 * @property int $order_user_active Признак активности пользователя
 * @property string $order_status Статус заказа
 * @property int $order_look Количество просмотров
 * @property array|null $order_strikes Жалобы
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderFromCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderFromCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderLook($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderNotMorePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderPersonalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderPriceUsd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderProductLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderRegisterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderStrikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderToAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderToCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderToCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderUserActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderUserCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderUserPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserId($value)
 * @mixin \Eloquent
 */
class Order extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_BAN = 'ban';
    const STATUS_SUCCESSFUL = 'successful';

    protected $primaryKey = 'order_id';
    protected $guarded = ['order_id'];
    public $timestamps = false;
    protected $casts = [
        'order_images' => 'array',
        'order_strikes' => 'array',
    ];
    protected $appends = [
        'order_is_favorite',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->user_id;
            $model->order_look = 0;
            $model->order_status = self::STATUS_ACTIVE;
            $model->order_register_date = $model->freshTimestamp();
        });

        static::saved(function ($model) {
            $order_id = $model->order_id ?: DB::getPdo()->lastInsertId();
            $model->order_url = Str::slug($model->order_name . ' ' . $order_id);

            $currency = getCurrencyNameBySymbol($model->order_currency);
            $model->order_price_usd = convertPriceToUsd($model->order_price, $currency);

            DB::table($model->table)
                ->where('order_id', $order_id)
                ->update([
                    'order_url' => $model->order_url,
                    'order_price_usd' => $model->order_price_usd,
                ]);
        });
    }

    public function getOrderStrikesAttribute($json)
    {
        if (is_null($json)) return [];

        if (is_array($json)) return $json;

        return json_decode($json, true);
    }

    public function getOrderIsFavoriteAttribute(): bool
    {
        $user = request()->user();

        if (empty($user->user_favorite_orders)) {
            return false;
        }

        return in_array($this->order_id, explode(',', $user->user_favorite_orders));
    }

    public function setOrderNameAttribute($value)
    {
        $this->attributes['order_name'] = strip_tags(strip_unsafe($value));
    }

    public function setOrderSizeAttribute($value)
    {
        $this->attributes['order_size'] = strip_tags(strip_unsafe($value));
    }

    public function setOrderWeightAttribute($value)
    {
        $this->attributes['order_weight'] = strip_tags(strip_unsafe($value));
    }

    public function setOrderTextAttribute($value)
    {
        $this->attributes['order_text'] = strip_tags(strip_unsafe($value), ['p', 'span', 'b', 'i', 's', 'u', 'strong', 'italic', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
    }

    public function setOrderFromAddressAttribute($value)
    {
        $this->attributes['order_from_address'] = strip_tags(strip_unsafe($value));
    }

    public function setOrderToAddressAttribute($value)
    {
        $this->attributes['order_to_address'] = strip_tags(strip_unsafe($value));
    }

    public function setOrderCurrencyAttribute($value)
    {
        $this->attributes['order_currency'] = config('app.currencies')[$value];
    }

    public function setOrderUserCurrencyAttribute($value)
    {
        $this->attributes['order_user_currency'] = config('app.currencies')[$value];
    }

    ### LINKS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Category::class, 'order_category', 'category_id')
            ->select(['category_id', "cat_name_{$lang} as category_name"])
            ->withDefault();
    }

    public function from_country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'order_from_country', 'country_id')
            ->select(['country_id', "country_name_{$lang} as country_name"])
            ->withDefault();
    }

    public function from_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'order_from_city', 'city_id')
            ->select(['city_id', "city_name_{$lang} as city_name"])
            ->withDefault();
    }

    public function to_country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'order_to_country', 'country_id')
            ->select(['country_id', "country_name_{$lang} as country_name"])
            ->withDefault();
    }

    public function to_city(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(City::class, 'order_to_city', 'city_id')
            ->select(['city_id', "city_name_{$lang} as city_name"])
            ->withDefault();
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'order_id', 'order_id');
    }

    ### SCOPES ###

    public function scopeSuccessful($query)
    {
        return $query->where('order_status', self::STATUS_SUCCESSFUL);
    }

    /**
     * Получить список избранных заказов авторизированного пользователя.
     *
     * @return array|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getFavorites()
    {
        $user = request()->user();

        if (empty($user->user_favorite_orders)) {
            return [];
        }

        return static::whereIn('order_id', explode(',', $user->user_favorite_orders))
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
                'category',
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
