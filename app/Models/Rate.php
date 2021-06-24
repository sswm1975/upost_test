<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Rate extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROGRESS = 'progress';
    public const STATUS_DISPUTE = 'dispute';
    public const STATUS_SUCCESSFUL = 'successful';

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

    function scopeDeadlineToday($query)
    {
        return $query->where([
            'rate_deadline' => Carbon::today()->toDateString(),
            'rate_status'   => self::STATUS_ACTIVE,
        ]);
    }

    function scopeDeadlineTermExpired($query, int $days = 0)
    {
        return $query->where('rate_status', self::STATUS_ACTIVE)
            ->where('rate_deadline', '>=', Carbon::today()->addDays($days)->toDateString());
    }
}
