<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Rate extends Model
{
    use TimestampSerializable;

    public const STATUS_ACTIVE     = 'active';      # создано владельцем маршрута
    public const STATUS_CANCELED   = 'canceled';    # отменено владельцем ставки/маршрута
    public const STATUS_REJECTED   = 'rejected';    # отклонено владельцем заказа
    public const STATUS_ACCEPTED   = 'accepted';    # владелец заказа принял ставку и успешно оплатил за товар/услугу
    public const STATUS_DISPUTE    = 'dispute';     #
    public const STATUS_SUCCESSFUL = 'successful';  #
    public const STATUS_BAN        = 'ban';         #

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
}
