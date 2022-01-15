<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function scopeOwner($query)
    {
        return $query->where('user_id', request()->user()->id);
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
