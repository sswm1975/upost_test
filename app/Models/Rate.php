<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Rate extends Model
{
    use TimestampSerializable;

    protected $table = 'rates';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = true;
    protected $casts = [
        'is_read' => 'boolean',
        'images'  => 'array',
    ];
    protected $appends = [
        'status_name',
        'images_thumb',
    ];
    protected $attributes = [
        'status'  => self::STATUS_ACTIVE,
        'is_read' => false,
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

    ### GETTERS ###

    public function getStatusNameAttribute(): string
    {
        return __("message.rate.statuses.$this->status");
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
        $this->attributes['comment'] = strip_tags(strip_unsafe($value), ['br']);
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
}
