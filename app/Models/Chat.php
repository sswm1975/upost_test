<?php

namespace App\Models;

use App\Events\MessagesCounterUpdate;
use App\Models\Traits\TimestampSerializable;
use App\Models\Traits\WithoutAppends;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Chat
 *
 * @property int $id Код
 * @property int $route_id Код маршрута
 * @property int $order_id Код заказа
 * @property int $customer_id Владелец заказа (Заказчик)
 * @property int $performer_id Владелец маршрута (Исполнитель)
 * @property int $customer_unread_count Кол-во непрочитанных сообщений заказчиком
 * @property int $performer_unread_count Кол-во непрочитанных сообщений исполнителем
 * @property string $status Статус
 * @property string $lock_status Статус блокировки
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read \App\Models\User $customer
 * @property-read \App\Models\Dispute|null $dispute
 * @property-read int $interlocutor_id
 * @property-read int $interlocutor_unread_count
 * @property-read string $status_name
 * @property-read \App\Models\User $interlocutor
 * @property-read \App\Models\Message|null $last_message
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Message[] $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\User $performer
 * @property-read \App\Models\Rate|null $rate
 * @property-read \App\Models\Route $route
 * @method static \Illuminate\Database\Eloquent\Builder|Chat active()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat closed()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat delivered()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat existsDispute()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat interlocutors()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat searchMessage(string $search)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCustomerUnreadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereLockStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat wherePerformerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat wherePerformerUnreadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat withoutAppends(array $appends = [])
 * @mixin \Eloquent
 */
class Chat extends Model
{
    use TimestampSerializable;
    use WithoutAppends;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    public const LOCK_STATUS_WITHOUT_LOCK = 'without_lock';
    public const LOCK_STATUS_ADD_MESSAGE_LOCK_ONLY_CUSTOMER = 'lock_add_message_only_customer';
    public const LOCK_STATUS_ADD_MESSAGE_LOCK_ONLY_PERFORMER = 'lock_add_message_only_performer';
    public const LOCK_STATUS_ADD_MESSAGE_LOCK_ALL = 'lock_add_message_all';
    public const LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_CUSTOMER = 'permit_one_message_only_customer';
    public const LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_PERFORMER = 'permit_one_message_only_performer';
    public const LOCK_STATUS_PERMIT_ONE_MESSAGE_ALL = 'permit_one_message_all';

    public const LOCK_STATUSES = [
        self::LOCK_STATUS_WITHOUT_LOCK                      => 'Без блокировки',
        self::LOCK_STATUS_ADD_MESSAGE_LOCK_ONLY_CUSTOMER    => 'Блокировано только заказчику',
        self::LOCK_STATUS_ADD_MESSAGE_LOCK_ONLY_PERFORMER   => 'Блокировано только исполнителю',
        self::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL              => 'Блокировано всем',
        self::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_CUSTOMER  => 'Разрешено одно сообщение заказчику',
        self::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_PERFORMER => 'Разрешено одно сообщение исполнителю',
        self::LOCK_STATUS_PERMIT_ONE_MESSAGE_ALL            => 'Разрешено всем по одному сообщению',
    ];

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $appends = ['interlocutor_id', 'interlocutor_unread_count'];
    protected $dates = ['created_at', 'updated_at',];

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

            if ($model->isDirty('lock_status')) {
                static::addSystemMessage($model->id, 'chat_lock_status.' . $model->lock_status);
            }
        });
    }

    ### GETTERS ###

    /**
     * Получить наименование статуса в зависимости от текущей локали.
     *
     * @return string
     */
    public function getStatusNameAttribute(): string
    {
        return __("message.chat.statuses.$this->status");
    }

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
     * Спор по чату.
     *
     * @return HasOne
     */
    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class)
            ->latest('id')
            ->limit(1);
    }

    /**
     * Последнее сообщение по чату.
     *
     * @return HasOne
     */
    public function last_message(): HasOne
    {
        return $this->hasOne(Message::class)
            ->latest('id')
            ->limit(1);
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
            $q->where('performer_id', request()->user()->id)->orWhere('customer_id', request()->user()->id);
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
     * Поиск чатов.
     * Поиск выполняется:
     * - по тексту сообщения;
     * - по имени и фамилии заказчика/исполнителя;
     * - по наименованию заказа.
     * Сортировка по весу общего поиска + последние сообщение.
     *
     * @param $query
     * @param string $search
     * @param int $auth_user_id
     * @return mixed
     *
     * Сырой запрос
     * SELECT
     *   chats.id,
     *   chats.customer_id,
     *   chats.performer_id,
     *   chats.performer_unread_count,
     *   chats.customer_unread_count,
     *   chats.route_id,
     *   chats.order_id,
     *   chats.status,
     *   chats.lock_status,
     *   chats.created_at,
     *   SUM(MATCH(u.name,u.surname) AGAINST ('test' IN BOOLEAN MODE)) AS user_weight,
     *   SUM(MATCH(o.name) AGAINST ('test' IN BOOLEAN MODE)) AS order_weight,
     *   SUM(MATCH(m.text) AGAINST ('test' IN BOOLEAN MODE)) AS messages_weight,
     *   SUM(
     *     MATCH(u.name,u.surname) AGAINST ('test' IN BOOLEAN MODE) +
     *     MATCH(o.name) AGAINST ('test' IN BOOLEAN MODE) +
     *     MATCH(m.text) AGAINST ('test' IN BOOLEAN MODE)
     *   ) AS all_weight,
     *   (SELECT MAX(id) FROM messages WHERE chat_id = chats.id) AS last_message_id
     * FROM `chats`
     * LEFT JOIN `users` AS `u` ON `u`.`id` = IF(chats.customer_id = 1, chats.performer_id, chats.customer_id) AND MATCH(u.name, u.surname) AGAINST ('test' IN BOOLEAN MODE)
     * LEFT JOIN `orders` AS `o` ON `o`.`id` = `chats`.`order_id` AND MATCH(o.name) AGAINST ('test' IN BOOLEAN MODE)
     * LEFT JOIN `messages` as `m` on `m`.`chat_id` = `chats`.`id` AND MATCH(m.text) AGAINST ('test' IN BOOLEAN MODE)
     * WHERE (`performer_id` = 1 or `customer_id` = 1)
     * GROUP BY `chats`.`id`
     * HAVING `all_weight` > 0
     * ORDER BY `all_weight` DESC, `last_message_id` DESC
     * LIMIT 5 OFFSET 0
     */
    public static function scopeBySearch($query, string $search, int $auth_user_id)
    {
        return $query
            ->leftJoin('users AS u', function ($join) use ($search, $auth_user_id) {
                $join->on('u.id', DB::raw("IF(chats.customer_id = {$auth_user_id}, chats.performer_id, chats.customer_id)"))
                    ->whereRaw('MATCH(u.name, u.surname) AGAINST (? IN BOOLEAN MODE)', [$search]);
            })
            ->leftJoin('orders AS o', function ($join) use ($search) {
                $join->on('o.id', '=', 'chats.order_id')
                    ->whereRaw('MATCH(o.name) AGAINST (? IN BOOLEAN MODE)', [$search]);
            })
            ->leftJoin('messages AS m', function ($join) use ($search) {
                $join->on('m.chat_id', '=', 'chats.id')
                    ->whereRaw('MATCH(m.text) AGAINST (? IN BOOLEAN MODE)', [$search]);
            })
            ->selectRaw('
                chats.id,
                chats.customer_id,
                chats.performer_id,
                chats.performer_unread_count,
                chats.customer_unread_count,
                chats.route_id,
                chats.order_id,
                chats.status,
                chats.lock_status,
                chats.created_at,
                SUM(MATCH(u.name,u.surname) AGAINST (? IN BOOLEAN MODE)) AS user_weight,
                SUM(MATCH(o.name) AGAINST (? IN BOOLEAN MODE)) AS order_weight,
                SUM(MATCH(m.text) AGAINST (? IN BOOLEAN MODE)) AS messages_weight,
                SUM(
	              MATCH(u.name,u.surname) AGAINST (? IN BOOLEAN MODE) +
                  MATCH(o.name) AGAINST (? IN BOOLEAN MODE) +
                  MATCH(m.text) AGAINST (? IN BOOLEAN MODE)
	            ) AS all_weight,
                (SELECT MAX(id) FROM messages WHERE chat_id = chats.id) AS last_message_id',
                [$search, $search, $search, $search, $search, $search]
            )
            ->groupBy('chats.id')
            ->having('all_weight', '>', 0)
            ->orderBy('all_weight', 'desc')
            ->orderBy('last_message_id', 'desc');
    }

    /**
     * Активные чаты.
     *
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('chats.status', self::STATUS_ACTIVE);
    }

    /**
     * Закрытые чаты.
     *
     * @param $query
     * @return mixed
     */
    public function scopeClosed($query)
    {
        return $query->where('chats.status', self::STATUS_CLOSED);
    }

    /**
     * Чаты, по которым есть спор.
     *
     * @param $query
     * @return mixed
     */
    public function scopeExistsDispute($query)
    {
        return $query->whereHas('dispute');
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
        return static::firstOrCreate(
            compact('route_id', 'order_id'),
            compact('performer_id', 'customer_id')
        );
    }

    /**
     * Броадкастим количество непрочитанных сообщений.
     *
     * @param int $recipient_id
     */
    public static function broadcastCountUnreadMessages(int $recipient_id)
    {
        try {
            broadcast(new MessagesCounterUpdate([
                'user_id'         => $recipient_id,
                'unread_messages' => Chat::getCountUnreadMessages($recipient_id),
            ]));
        } catch (\Exception $e) {

        }
    }

    /**
     * Получить количество непрочитанных сообщений.
     *
     * @param int $user_id
     * @return int
     */
    public static function getCountUnreadMessages(int $user_id): int
    {
        $customer_unread_count = Chat::active()
            ->where('customer_id', $user_id)
            ->where('customer_unread_count', '>', 0)
            ->sum('customer_unread_count');

        $performer_unread_count = Chat::active()
            ->where('performer_id', $user_id)
            ->where('performer_unread_count', '>', 0)
            ->sum('performer_unread_count');

        return (int) $customer_unread_count + (int) $performer_unread_count;
    }

    /**
     * Добавить системное сообщение.
     *
     * @param int $chat_id
     * @param mixed $alias
     * @param array $extra
     * @return bool
     */
    public static function addSystemMessage(int $chat_id, $alias, array $extra = []): bool
    {
        if (is_string($alias)) {
            $alias = [$alias];
        }
        foreach ($alias as $text) {
            $message = new Message;
            $message->chat_id = $chat_id;
            $message->user_id = SYSTEM_USER_ID;
            $message->text    = $text;
            $message->save();
        }

        if (! empty($extra)) {
            self::whereKey($chat_id)->update($extra);
        }

        return true;
    }
}
