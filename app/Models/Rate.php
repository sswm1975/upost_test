<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rate extends Model
{
    protected $table = 'rate';
    protected $primaryKey = 'rate_id';
    protected $guarded = ['rate_id'];
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->rate_status = 'active';
            $model->read_rate = 0;
            $model->rate_date  = date('Y-m-d H:i');
        });
    }

    public function setRateTextAttribute($value)
    {
        $this->attributes['rate_text'] = strip_tags(strip_unsafe($value), ['br']);
    }

    public function setRateCurrencyAttribute($value)
    {
        $this->attributes['rate_currency'] = config('app.currencies')[$value];
    }

    function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

}
