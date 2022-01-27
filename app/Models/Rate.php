<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Rate extends Model
{
    use TimestampSerializable;

    public const STATUS_ACTIVE = 'active';           # создано владельцем маршрута
    public const STATUS_CANCELED = 'canceled';       # отменено владельцем ставки/маршрута
    public const STATUS_REJECTED = 'rejected';       # отклонено владельцем заказа
    public const STATUS_ACCEPTED = 'accepted';       # владелец заказа принял ставку и успешно оплатил за товар/услугу
    public const STATUS_DISPUTE = 'dispute';         #
    public const STATUS_SUCCESSFUL = 'successful';   #
    public const STATUS_BAN = 'ban';                 #

    protected $table = 'rates';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = true;
    protected $casts = [
        'is_read' => 'boolean',
    ];
    protected $appends = [
        'status_name',
    ];

    ### GETTERS ###

    public function getStatusNameAttribute(): string
    {
        return __("message.rate.statuses.$this->status");
    }

    ### SETTERS ###

    public function setCommentAttribute($value)
    {
        $this->attributes['comment'] = strip_tags(strip_unsafe($value), ['br']);
    }

    ### RELATIONS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')->withDefault();
    }

    function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id')->withDefault();
    }

    ### SCOPES ###

    public function scopeOwner($query)
    {
        return $query->where('user_id', request()->user()->id);
    }

    function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
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

    ### QUERIES ###

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
            'is_read'   => 0,
            'status'    => self::STATUS_ACTIVE,
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
            'is_read'   => 1,
            'status'    => self::STATUS_ACTIVE,
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
            'status'    => self::STATUS_ACTIVE,
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
            'is_read'   => 0,
            'status'    => self::STATUS_ACTIVE,
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
            'is_read'     => 1,
            'status'      => self::STATUS_ACTIVE,
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
            'status'    => self::STATUS_ACTIVE,
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
