<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use App\Models\Traits\WithoutAppends;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * App\Models\Rate
 *
 * @property int $id Код
 * @property int $user_id Код пользователя
 * @property int $route_id Код маршрута
 * @property int $order_id Код заказа
 * @property int $chat_id Код чата
 * @property mixed $amount Сумма дохода
 * @property string $currency Валюта дохода
 * @property mixed $amount_usd Сумма дохода в долларах
 * @property string $deadline Дата выполнения
 * @property string|null $comment Комментарий
 * @property bool $viewed_by_customer Новая ставка просмотрена заказчиком?
 * @property bool $viewed_by_performer Подтвержденная ставка просмотрена исполнителем?
 * @property array $images Фотографии купленного заказа
 * @property string $status Статус
 * @property \Illuminate\Support\Carbon $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read \App\Models\Chat|null $chat
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Dispute[] $disputes
 * @property-read int|null $disputes_count
 * @property-read array $images_thumb
 * @property-read string $status_name
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Route $route
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Rate active()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate deadlineTermExpired(int $days = 0)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate deadlineToday()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate delivered()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate notViewedByPerformer()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate owner()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate query()
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereAmountUsd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereViewedByCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate whereViewedByPerformer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rate withoutAppends()
 * @mixin \Eloquent
 */
class Rate extends Model
{
    use TimestampSerializable;
    use WithoutAppends;

    protected $table = 'rates';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = true;
    protected $casts = [
        'amount'              => 'decimal:2',
        'amount_usd'          => 'decimal:2',
        'viewed_by_customer'  => 'boolean',
        'viewed_by_performer' => 'boolean',
        'images'              => 'array',
    ];
    protected $appends = [
        'status_name',
        'images_thumb',
    ];
    protected $attributes = [
        'status'              => self::STATUS_ACTIVE,
        'viewed_by_customer'  => false,
        'viewed_by_performer' => false,
    ];

    public const STATUS_ACTIVE     = 'active';     # владелец маршрута создал ставку
    public const STATUS_CANCELED   = 'canceled';   # владелец маршрута отменил ставку
    public const STATUS_REJECTED   = 'rejected';   # владелец заказа отклонил ставку
    public const STATUS_ACCEPTED   = 'accepted';   # владелец заказа принял ставку и успешно оплатил за товар
    public const STATUS_BUYED      = 'buyed';      # владелец маршрута купил товар
    public const STATUS_SUCCESSFUL = 'successful'; # владелец заказа получил от путешественника товар
    public const STATUS_DONE       = 'done';       # администрация перечислила деньги владельцу маршрута, ставка выполнена
    public const STATUS_FAILED     = 'failed';     # неудачный после спора
    public const STATUS_BANNED     = 'banned';     # забаненная ставка за нарушения, устанавливается администрацией

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_CANCELED,
        self::STATUS_REJECTED,
        self::STATUS_ACCEPTED,
        self::STATUS_BUYED,
        self::STATUS_SUCCESSFUL,
        self::STATUS_DONE,
        self::STATUS_FAILED,
        self::STATUS_BANNED,
    ];

    # статусы доставки
    public const STATUSES_DELIVERED = [
        self::STATUS_ACCEPTED,
        self::STATUS_BUYED,
    ];

    # подтвержденные статусы
    public const STATUSES_CONFIRMED = [
        self::STATUS_ACCEPTED,
        self::STATUS_BUYED,
        self::STATUS_SUCCESSFUL,
        self::STATUS_DONE,
    ];

    ### GETTERS ###

    public function getStatusNameAttribute(): string
    {
        return __("message.rate.statuses.$this->status");
    }

    public function getChatIdAttribute($chat_id): int
    {
        return $chat_id ?? 0;
    }

    public function getImagesAttribute($images): array
    {
        if (is_null($images)) return [];

        if (is_string($images)) {
            $images = json_decode($images);
        }

        $link_images = [];
        foreach ($images as $image) {
            $link_images[] = asset("storage/{$this->user_id}/chats/{$image}");
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

    ### SETTERS ###

    public function setCommentAttribute($value)
    {
        if (! empty($value)) {
            $this->attributes['comment'] = strip_tags(strip_unsafe($value), ['br']);
        }
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

    function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'id')->withDefault();
    }

    /**
     * Споры по ставке.
     *
     * @return HasMany
     */
    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
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
     * Ставки, которые находятся в одном из статусов "Доставка".
     *
     * @param $query
     * @return mixed
     */
    function scopeDelivered($query)
    {
        return $query->whereIn('status', self::STATUSES_DELIVERED);
    }

    /**
     * Подтвержденные ставки.
     *
     * @param $query
     * @return mixed
     */
    function scopeConfirmed($query)
    {
        return $query->whereIn('status', self::STATUSES_CONFIRMED);
    }

    /**
     * Получить ставку по её коду при условии, что авторизированный пользователь является владельцем ставки (маршрута).
     *
     * @param $query
     * @param mixed $id
     * @param array $statuses
     * @return mixed
     */
    protected function scopeByKeyForRateOwner($query, $id, array $statuses = [self::STATUS_ACTIVE])
    {
        return $query->whereKey($id)->owner()->whereIn('status', $statuses);
    }

    /**
     * Получить ставку по её коду при условии, что авторизированный пользователь является владельцем заказа.
     *
     * @param $query
     * @param mixed $id             код или список кодов
     * @param array $rate_statuses  список статусов ставки, по умолчанию active
     * @param array $order_statuses список статусов для заказа
     * @return mixed
     */
    protected function scopeByKeyForOrderOwner($query, $id, array $rate_statuses = [self::STATUS_ACTIVE], array $order_statuses = [])
    {
        return $query->whereKey($id)
            ->whereIn('status', $rate_statuses)
            ->whereHas('order', function($query) use ($order_statuses) {
                $query->ownerWithStatuses($order_statuses);
            });
    }

    function scopeNotViewedByPerformer($query)
    {
        return $query->where('viewed_by_performer', 0);
    }

    function scopeDeadlineToday($query)
    {
        return $query->active()->where('deadline', Carbon::today()->toDateString());
    }

    function scopeDeadlineTermExpired($query, int $days = 0)
    {
        return $query->active()->where('deadline', '>=', Carbon::today()->addDays($days)->toDateString());
    }
}
