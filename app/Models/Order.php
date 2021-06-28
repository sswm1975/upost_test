<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_BAN = 'ban';

    protected $primaryKey = 'order_id';
    protected $guarded = ['order_id'];
    public $timestamps = false;
    protected $casts = [
        'order_images' => 'array',
        'order_strikes' => 'json',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->user_id;
            $model->order_look = 0;
            $model->order_register_date = $model->freshTimestamp();
        });

        static::saved(function ($model) {
            $order_id = $model->order_id ?: DB::getPdo()->lastInsertId();
            $model->order_url = Str::slug($model->order_name . ' ' . $order_id);
            DB::table($model->table)->where('order_id', $order_id)->update(['order_url' => $model->order_url]);
        });
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
