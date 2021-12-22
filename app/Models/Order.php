<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * App\Models\Order
 *
 * @property int $id Код
 * @property int $user_id Код пользователя
 * @property string $register_date Дата регистрации
 * @property string $name Наименование
 * @property string|null $slug Слаг
 * @property int|null $category_id Код категории
 * @property string $price Цена
 * @property string $price_usd Цена в долларах
 * @property string|null $currency Валюта
 * @property int|null $products_count Количество товаров
 * @property string|null $size Размер товара
 * @property string|null $weight Вес товара
 * @property string|null $product_link Ссылка на товар на чужом сайте
 * @property string|null $description Описание заказа
 * @property array $images Фотографии заказа
 * @property int|null $from_country_id Код страны начала заказа
 * @property int|null $from_city_id Код города начала заказа
 * @property string|null $from_address Точный адрес старта заказа
 * @property int|null $to_country_id Код страны окончания заказа
 * @property int|null $to_city_id Код города окончания заказа
 * @property string|null $to_address Точный адрес прибытия заказа
 * @property string|null $fromdate Дата начала заказа
 * @property string|null $tilldate Дата окончания заказа
 * @property int $personal_price Признак персонального вознаграждения
 * @property string $user_price Сумма персонального вознаграждения
 * @property string|null $user_currency Валюта персонального вознаграждения
 * @property int $not_more_price Признак "Не принимать ставки выше данной цены"
 * @property int $is_user_active Признак активности пользователя
 * @property int $looks Количество просмотров
 * @property string $status Статус заказа
 * @property array|null $strikes Жалобы
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\City|null $from_city
 * @property-read \App\Models\Country|null $from_country
 * @property-read array $images_medium
 * @property-read array $images_original
 * @property-read array $images_thumb
 * @property-read bool $is_favorite
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rate[] $rates
 * @property-read int|null $rates_count
 * @property-read \App\Models\Review|null $review
 * @property-read \App\Models\City|null $to_city
 * @property-read \App\Models\Country|null $to_country
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order successful()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereFromCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereFromCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereFromdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereIsUserActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereLooks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereNotMorePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePersonalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePriceUsd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereProductLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereProductsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereRegisterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStrikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTilldate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereToAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereToCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereToCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereWeight($value)
 * @mixin \Eloquent
 */
class Order extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_BAN = 'ban';
    const STATUS_SUCCESSFUL = 'successful';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_CLOSED,
        self::STATUS_BAN,
        self::STATUS_SUCCESSFUL,
    ];

    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = [
        'images' => 'array',
        'strikes' => 'array',
    ];
    protected $appends = [
        'short_name',
        'status_name',
        'is_favorite',
        'images_thumb',
        'images_medium',
        'images_original',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->id;
            $model->looks = 0;
            $model->slug = Str::slug($model->name);
            $model->status = self::STATUS_ACTIVE;
            $model->register_date = $model->freshTimestamp();
        });

        static::saved(function ($model) {
            if (empty($model->slug)) {
                $id = $model->id ?: DB::getPdo()->lastInsertId();
                $model->slug = Str::slug($model->name . ' ' . $id);

                $currency = getCurrencyNameBySymbol($model->currency);
                $model->price_usd = convertPriceToUsd($model->price, $currency);

                DB::table($model->table)
                    ->where('id', $id)
                    ->update([
                        'slug' => $model->slug,
                        'price_usd' => $model->price_usd,
                    ]);
            }
        });
    }

    ### GETTERS ###

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

    public function getIsFavoriteAttribute(): bool
    {
        $user = request()->user();

        if (empty($user->favorite_orders)) {
            return false;
        }

        return in_array($this->id, explode(',', $user->favorite_orders));
    }

    ### SETTERS ###

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = !empty($value) ? strip_tags(strip_unsafe($value)) : null;
    }

    public function setSizeAttribute($value)
    {
        $this->attributes['size'] = !empty($value) ? strip_tags(strip_unsafe($value)) : null;
    }

    public function setWeightAttribute($value)
    {
        $this->attributes['weight'] = !empty($value) ? strip_tags(strip_unsafe($value)) : null;
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = !empty($value)
            ? strip_tags(strip_unsafe($value), ['p', 'span', 'b', 'i', 's', 'u', 'strong', 'italic', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])
            : null;
    }

    public function setFromAddressAttribute($value)
    {
        $this->attributes['from_address'] = !empty($value) ? strip_tags(strip_unsafe($value)) : null;
    }

    public function setToAddressAttribute($value)
    {
        $this->attributes['to_address'] = !empty($value) ? strip_tags(strip_unsafe($value)) : null;
    }

    public function setUserPriceAttribute($value)
    {
        $this->attributes['user_price'] = !empty($value) ? $value : 0;
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

    ### LINKS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function category(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Category::class, 'category_id', 'id')
            ->select(['id', "name_{$lang} as name"])
            ->withDefault();
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
        return $this->hasMany(Rate::class, 'order_id', 'id');
    }

    public function review(): MorphOne
    {
        return $this->morphOne(Review::class, 'reviewable');
    }

    ### SCOPES ###

    public function scopeSuccessful($query)
    {
        return $query->whereStatus(self::STATUS_SUCCESSFUL);
    }

    /**
     * Получить список избранных заказов авторизированного пользователя.
     *
     * @return array|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getFavorites()
    {
        $user = request()->user();

        if (empty($user->favorite_orders)) {
            return [];
        }

        return static::whereIn('id', explode(',', $user->favorite_orders))
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
                'category',
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
