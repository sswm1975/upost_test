<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * App\Models\Rate
 *
 * @property int $rate_id Код
 * @property int $parent_id Код родителя
 * @property int $who_start Код пользователя (Кто)
 * @property int $user_id Код пользователя (Кому)
 * @property int $order_id Код заказа
 * @property int $route_id Код маршрута
 * @property int $read_rate Признак прочтения ставки
 * @property string $rate_type Тип ставки
 * @property string $rate_status Статус ставки
 * @property string $rate_date Дата ставки
 * @property string|null $rate_deadline Дата выполнения
 * @property string $rate_price Цена ставки
 * @property string $rate_currency Валюта ставки
 * @property string|null $rate_text Текст ставки
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Route $route
 * @method static \Illuminate\Database\Eloquent\Builder|Rate deadlineTermExpired(int $days = 0)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate deadlineToday()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate query()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRateCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRateDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRateDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRatePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRateStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRateText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRateType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereReadRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereWhoStart($value)
 * @mixin \Eloquent
 */
class Rate extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROGRESS = 'progress';
    public const STATUS_DISPUTE = 'dispute';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_BAN = 'ban';

    protected $table = 'rate';
    protected $primaryKey = 'rate_id';
    protected $guarded = ['rate_id'];
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->user_id;
            $model->rate_status = self::STATUS_ACTIVE;
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
