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

class Order extends Model
{
    use TimestampSerializable;

    const STATUS_ACTIVE = 'active';
    const STATUS_IN_WORK = 'in_work';
    const STATUS_CLOSED = 'closed';
    const STATUS_BANNED = 'banned';
    const STATUS_SUCCESSFUL = 'successful';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_IN_WORK,
        self::STATUS_CLOSED,
        self::STATUS_BANNED,
        self::STATUS_SUCCESSFUL,
    ];

    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $casts = [
        'images' => 'array',
        'strikes' => 'array',
    ];
    protected $appends = [
        'short_name',
        'status_name',
        'images_thumb',
        'images_medium',
        'images_original',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->slug = Str::slug($model->name) . '-'. Str::random(8);
            $model->register_date = Date::now()->format('Y-m-d');
        });

        self::saving(function ($model) {
            $model->price_usd = convertPriceToUsd($model->price, $model->currency);
            $model->user_price_usd = convertPriceToUsd($model->user_price, $model->user_currency);
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
     * @return mixed
     */
    public function scopeSearchByRoutes($query, bool $only_new = false)
    {
        return $query->where('orders.status', self::STATUS_ACTIVE)
            ->whereBetweenColumns('routes.deadline', ['orders.register_date', 'orders.deadline'])
            ->whereColumn('orders.from_country_id', 'routes.from_country_id')
            ->whereColumn('orders.to_country_id', 'routes.to_country_id')
            ->where(function($query) {
                return $query->whereColumn('orders.from_city_id', 'routes.from_city_id')
                    ->orWhere(function ($query) {
                        return $query->whereNull('orders.from_city_id')->where('routes.from_city_id', '>', 0);
                    })
                    ->orWhere(function ($query) {
                        return $query->whereNull('routes.from_city_id')->where('orders.from_city_id', '>', 0);
                    });
            })
            ->where(function($query) {
                return $query->whereColumn('orders.to_city_id', 'routes.to_city_id')
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
}
