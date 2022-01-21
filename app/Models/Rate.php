<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * App\Models\Rate
 *
 * @property int $id Код
 * @property int $parent_id Код родителя
 * @property int $who_start Код пользователя (Кто)
 * @property int $user_id Код пользователя (Кому)
 * @property int $order_id Код заказа
 * @property int $route_id Код маршрута
 * @property string $type Тип ставки
 * @property string $deadline Дата выполнения
 * @property string $price Цена ставки
 * @property string $currency Валюта ставки
 * @property string|null $text Текст ставки
 * @property int $is_read Признак прочтения ставки
 * @property string $status Статус ставки
 * @property string $created_at Дата ставки
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Route $route
 * @method static \Illuminate\Database\Eloquent\Builder|Rate deadlineTermExpired(int $days = 0)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate deadlineToday()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate query()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate typeOrder()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate typeRoute()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereType($value)
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

    public const TYPE_ORDER = 'order';
    public const TYPE_ROUTE = 'route';

    protected $table = 'rates';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->id;
            $model->status = self::STATUS_ACTIVE;
            $model->is_read = 0;
            $model->created_at  = $model->freshTimestamp();
        });
    }

    public function setTextAttribute($value)
    {
        $this->attributes['text'] = strip_tags(strip_unsafe($value), ['br']);
    }

    public function setCurrencyAttribute($value)
    {
        $this->attributes['currency'] = config('app.currencies')[$value];
    }

    function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')->withDefault();
    }

    function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id')->withDefault();
    }

    function scopeTypeOrder($query)
    {
        return $query->where('type', self::TYPE_ORDER);
    }

    function scopeTypeRoute($query)
    {
        return $query->where('type', self::TYPE_ROUTE);
    }

    function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    function scopeNotRead($query)
    {
        return $query->where('is_read', 0);
    }

    function scopeDeadlineToday($query)
    {
        return $query->active()->where('deadline', Carbon::today()->toDateString());
    }

    function scopeDeadlineTermExpired($query, int $days = 0)
    {
        return $query->active()->where('deadline', '>=', Carbon::today()->addDays($days)->toDateString());
    }

    /**
     * Получить ставки по выбранному заказу.
     *
     * @param int $order_id
     * @return array
     */
    public static function getRatesByOrder(int $order_id)
    {
        return static::query()
            ->with([
                'order.user:' . implode(',', User::FIELDS_FOR_SHOW),
                'order.from_country',
                'order.from_city',
                'order.to_country',
                'order.to_city',
                'route.user:' . implode(',', User::FIELDS_FOR_SHOW),
                'route.from_country',
                'route.from_city',
                'route.to_country',
                'route.to_city',
            ])
            ->where([
                'order_id' => $order_id,
                'type'     => self::TYPE_ORDER,
            ])
            ->where(function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->whereUserId(request()->user()->id);
                })->orWhereHas('route', function ($q) {
                    $q->whereUserId(request()->user()->id);
                });
            })
            ->oldest()
            ->get()
            ->groupBy('who_start')
            ->all();
    }

    /**
     * Новые ставки по выбранному заказу.
     * Условия:
     * - order_id = параметр КОД ЗАКАЗА
     * - parent_id = 0
     * - is_read = 0
     * - status = active
     * - type = order
     * - нет дочерних ставок
     *
     * @param int $order_id
     * @return mixed
     */
    public static function getNewRatesByOrder(int $order_id)
    {
        return static::where([
            'order_id'  => $order_id,
            'parent_id' => 0,
            'is_read'   => 0,
            'status'    => self::STATUS_ACTIVE,
            'type'      => self::TYPE_ORDER,
        ])->whereNotExists(function ($query) {
            $query->selectRaw(1)->from('rates as rc')->whereRaw('rc.parent_id = rates.id');
        })->with([
            'route:id,user_id,from_country_id,from_city_id,to_country_id,to_city_id,looks',
            'route.user:id,name,surname,creator_rating,freelancer_rating,register_date,last_active,photo',
            'route.from_country',
            'route.from_city',
            'route.to_country',
            'route.to_city',
        ])->get();
    }

    /**
     * Просмотренные ставки по выбранному заказу.
     * Условия:
     * - order_id = параметр КОД ЗАКАЗА
     * - parent_id = 0
     * - is_read = 1
     * - status = active
     * - type = order
     * - нет дочерних ставок
     *
     * @param int $order_id
     * @return mixed
     */
    public static function getReadRatesByOrder(int $order_id)
    {
        return static::where([
            'order_id'  => $order_id,
            'parent_id' => 0,
            'is_read'   => 1,
            'status'    => self::STATUS_ACTIVE,
            'type'      => self::TYPE_ORDER,
        ])->whereNotExists(function ($query) {
            $query->selectRaw(1)->from('rates as rc')->whereRaw('rc.parent_id = rates.id');
        })->with([
            'route:id,user_id,from_country_id,from_city_id,to_country_id,to_city_id,looks',
            'route.user:id,name,surname,creator_rating,freelancer_rating,register_date,last_active,photo',
            'route.from_country',
            'route.from_city',
            'route.to_country',
            'route.to_city',
        ])->get();
    }

    /**
     * Ставки с наличием дочерних ставок по выбранному заказу.
     * Условия:
     * - order_id = параметр КОД ЗАКАЗА
     * - parent_id = 0
     * - status = active
     * - type = order
     * - есть дочерние ставки
     *
     * @param int $order_id
     * @return mixed
     */
    public static function getExistsChildRatesByOrder(int $order_id)
    {
        return static::where([
            'order_id'  => $order_id,
            'parent_id' => 0,
            'status'    => self::STATUS_ACTIVE,
            'type'      => self::TYPE_ORDER,
        ])->whereExists(function ($query) {
            $query->selectRaw(1)->from('rates as rc')->whereRaw('rc.parent_id = rates.id');
        })->selectRaw('rates.*, (select rm.user_id from rates as rm where rm.parent_id = rates.id order by id desc limit 1) as last_message_from')
        ->with([
            'route:id,user_id,from_country_id,from_city_id,to_country_id,to_city_id,looks',
            'route.user:id,name,surname,creator_rating,freelancer_rating,register_date,last_active,photo',
            "route.from_country",
            'route.from_city',
            "route.to_country",
            'route.to_city',
        ])->get();
    }

    /**
     * Новые ставки по выбранному маршруту.
     * Условия:
     * - route_id = параметр КОД МАРШРУТА
     * - parent_id = 0
     * - is_read = 0
     * - status = active
     * - type = route
     * - нет дочерних ставок
     *
     * @param int $route_id
     * @return mixed
     */
    public static function getNewRatesByRoute(int $route_id)
    {
        return static::where([
            'route_id'  => $route_id,
            'parent_id' => 0,
            'is_read'   => 0,
            'status'    => self::STATUS_ACTIVE,
            'type'      => self::TYPE_ROUTE,
        ])->whereNotExists(function ($query) {
            $query->selectRaw(1)->from('rates as rc')->whereRaw('rc.parent_id = rates.id');
        })->with([
            'order:id,user_id,from_country_id,from_city_id,to_country_id,to_city_id,looks',
            'order.user:id,name,surname,creator_rating,freelancer_rating,register_date,last_active,photo',
            'order.from_country',
            'order.from_city',
            'order.to_country',
            'order.to_city',
        ])->get();
    }

    /**
     * Просмотренные ставки по выбранному маршруту.
     * Условия:
     * - route_id = параметр КОД МАРШРУТА
     * - parent_id = 0
     * - is_read = 1
     * - status = active
     * - type = route
     * - нет дочерних ставок
     *
     * @param int $route_id
     * @return mixed
     */
    public static function getReadRatesByRoute(int $route_id)
    {
        return static::where([
            'route_id'    => $route_id,
            'parent_id'   => 0,
            'is_read'     => 1,
            'status'      => self::STATUS_ACTIVE,
            'type'        => self::TYPE_ROUTE,
        ])->whereNotExists(function ($query) {
            $query->selectRaw(1)->from('rates as rc')->whereRaw('rc.parent_id = rates.id');
        })->with([
            'order:id,user_id,from_country_id,from_city_id,to_country_id,to_city_id,looks',
            'order.user:id,name,surname,creator_rating,freelancer_rating,register_date,last_active,photo',
            'order.from_country',
            'order.from_city',
            'order.to_country',
            'order.to_city',
        ])->get();
    }

    /**
     * Ставки с наличием дочерних ставок по выбранному маршруту.
     * Условия:
     * - route_id = параметр КОД МАРШРУТА
     * - parent_id = 0
     * - status = active
     * - type = route
     * - есть дочерние ставки
     *
     * @param int $route_id
     * @return mixed
     */
    public static function getExistsChildRatesByRoute(int $route_id)
    {
        return static::where([
            'route_id'  => $route_id,
            'parent_id' => 0,
            'status'    => self::STATUS_ACTIVE,
            'type'      => self::TYPE_ROUTE,
        ])->whereExists(function ($query) {
            $query->selectRaw(1)->from('rates as rc')->whereRaw('rc.parent_id = rates.id');
        })->selectRaw('rates.*, (select rm.user_id from rates as rm where rm.parent_id = rates.id order by id desc limit 1) as last_message_from')
        ->with([
            'order:id,user_id,from_country_id,from_city_id,to_country_id,to_city_id,looks',
            'order.user:id,name,surname,creator_rating,freelancer_rating,register_date,last_active,photo',
            'order.from_country',
            'order.from_city',
            'order.to_country',
            'order.to_city',
        ])->get();
    }
}
