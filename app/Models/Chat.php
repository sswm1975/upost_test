<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\Chat
 *
 * @property int $id Код
 * @property int $route_id Код маршрута
 * @property int $order_id Код заказа
 * @property int $performer_id Владелец маршрута (Исполнитель)
 * @property int $customer_id Владелец заказа (Заказчик)
 * @property int $performer_unread_count Кол-во непрочитанных сообщений исполнителем
 * @property int $customer_unread_count Кол-во непрочитанных сообщений заказчиком
 * @property string $status Статус
 * @property string|null $created_at Добавлено
 * @property string|null $updated_at Изменено
 * @property-read \App\Models\User $customer
 * @property-read int $interlocutor_id
 * @property-read int $interlocutor_unread_count
 * @property-read \App\Models\User $interlocutor
 * @property-read \App\Models\Message|null $last_message
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Message[] $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\User $performer
 * @property-read \App\Models\Route $route
 * @method static \Illuminate\Database\Eloquent\Builder|Chat closed()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat interlocutors()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat waiting()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCustomerUnreadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat wherePerformerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat wherePerformerUnreadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Chat extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $appends = ['interlocutor_id', 'interlocutor_unread_count'];
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    ### BOOT ###

    /**
     * Boot model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->status = self::STATUS_ACTIVE;
            $model->customer_unread_count = 0;
            $model->performer_unread_count = 0;
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
    }

    ### GETTERS ###

    /**
     * Определяем код пользователя, который является собеседником с авторизированным пользователем.
     *
     * @return int
     */
    public function getInterlocutorIdAttribute(): int
    {
        if (empty(request()->user()->id)) return 0;

        return request()->user()->id == $this->performer_id ? $this->customer_id : $this->performer_id;
    }

    /**
     * Получить количество непрочитанных сообщений от собеседника.
     *
     * @return int
     */
    public function getInterlocutorUnreadCountAttribute(): int
    {
        if (empty(request()->user()->id)) return 0;

        return request()->user()->id == $this->performer_id ? $this->customer_unread_count : $this->performer_unread_count;
    }

    ### RELATIONS ###

    /**
     * Маршрут.
     *
     * @return BelongsTo
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Заказ.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Владелец маршрута (Исполнитель).
     *
     * @return BelongsTo
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Владелец заказа (Заказчик).
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Собеседник чата.
     *
     * @return BelongsTo
     */
    public function interlocutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interlocutor_id', 'id');
    }

    /**
     * Сообщения по чату.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Ставка по чату.
     *
     * @return HasOne
     */
    public function rate(): HasOne
    {
        return $this->hasOne(Rate::class);
    }

    /**
     * Последнее сообщение по чату.
     *
     * @return HasOne
     */
    public function last_message(): HasOne
    {
        return $this->hasOne(Message::class)->latest();
    }

    ### SCOPES ###

    /**
     * Авторизированный пользователь является собеседником чата.
     *
     * @param $query
     * @return mixed
     */
    public function scopeInterlocutors($query)
    {
        return $query->where(function ($q) {
           $q->where('performer_id', request()->user()->id)
               ->orWhere('customer_id', request()->user()->id);
        });
    }

    /**
     * Чаты с непрочитанными сообщениями для авторизированного пользователя, так называемые "Чаты в ожидании".
     *
     * @param $query
     * @return mixed
     */
    public function scopeWaiting($query)
    {
        return $query->where(function ($q) {
            $q->where('performer_id', request()->user()->id)
                ->where('customer_unread_count', '>', 0);
        })->orWhere(function ($q) {
            $q->where('customer_id', request()->user()->id)
                ->where('performer_unread_count', '>', 0);
        });
    }

    /**
     * Чаты, по которым ставка находится в статусе "Доставка".
     *
     * @param $query
     * @return mixed
     */
    public function scopeDelivered($query)
    {
        return $query->whereHas('rate', function ($q) {
            $q->delivered();
        });
    }

    /**
     * Чаты, по которым в сообщениях есть поисковая строка.
     *
     * @param $query
     * @param string $search
     * @return mixed
     */
    public function scopeSearchMessage($query, string $search)
    {
        return $query->whereHas('messages', function ($q) use ($search) {
            $q->where('text', 'like', '%'.$search.'%');
        });
    }

    /**
     * Закрытые чаты.
     *
     * @param $query
     * @return mixed
     */
    public function scopeClosed($query)
    {
        return $query->whereStatus(self::STATUS_CLOSED);
    }

    ### QUERIES ###

    /**
     * Возвращает существующий чат или создает новый чат.
     *
     * @param int $route_id
     * @param int $order_id
     * @param int $performer_id
     * @param int $customer_id
     * @return Chat|Model
     */
    public static function searchOrCreate(int $route_id, int $order_id, int $performer_id, int $customer_id)
    {
        return static::firstOrCreate([
            'route_id' => $route_id,
            'order_id' => $order_id,
        ], [
            'performer_id' => $performer_id,
            'customer_id'  => $customer_id,
        ]);
    }
}
