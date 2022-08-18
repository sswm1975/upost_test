<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\Dispute
 *
 * @property int $id Код
 * @property int $problem_id Код проблема
 * @property int $user_id Код пользователя
 * @property int $rate_id Код ставки
 * @property int $chat_id Код чата
 * @property int $message_id Код сообщения
 * @property string $status Статус спора
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property \Illuminate\Support\Carbon|null $deadline Дедлайн
 * @property int|null $closed_user_id Код пользователя закрывший спор
 * @property int|null $admin_user_id Менеджер закрепленный за спором
 * @property int $unread_messages_count Кол-во непрочитанных сообщений менеджером
 * @property int|null $dispute_closed_reason_id Причина закрытия спора
 * @property string|null $reason_closing_description Детальное описание закрытия спора менеджером чата
 * @property-read Administrator|null $admin_user
 * @property-read \App\Models\Chat $chat
 * @property-read \App\Models\User|null $closed_user
 * @property-read \App\Models\DisputeClosedReason|null $dispute_closed_reason
 * @property-read string $status_name
 * @property-read \App\Models\Message $message
 * @property-read \App\Models\DisputeProblem $problem
 * @property-read \App\Models\Rate $rate
 * @property-read \App\Models\Track|null $track
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute active()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute appointed()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute closed()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute existsForChat(int $chat_id)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute inWork()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute query()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereAdminUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereClosedUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereDisputeClosedReasonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereProblemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereReasonClosingDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereUnreadMessagesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereUserId($value)
 * @mixin \Eloquent
 */
class Dispute extends Model
{
    use TimestampSerializable;

    const STATUS_ACTIVE    = 'active';
    const STATUS_APPOINTED = 'appointed';
    const STATUS_IN_WORK   = 'in_work';
    const STATUS_CLOSED    = 'closed';
    const STATUS_CANCELED  = 'canceled';

    # все статусы
    const STATUSES = [
        self::STATUS_ACTIVE    => 'Активный',
        self::STATUS_APPOINTED => 'Назначен',
        self::STATUS_IN_WORK   => 'В работе',
        self::STATUS_CLOSED    => 'Закрытый',
        self::STATUS_CANCELED  => 'Отмененный',
    ];

    # статусы для действующих споров
        const STATUSES_ACTING = [
        self::STATUS_ACTIVE,
        self::STATUS_APPOINTED,
        self::STATUS_IN_WORK,
    ];

    # цвета статусов для админки
    const STATUS_COLORS = [
        self::STATUS_ACTIVE    => 'danger',
        self::STATUS_APPOINTED => 'warning',
        self::STATUS_IN_WORK   => 'success',
        self::STATUS_CLOSED    => 'info',
        self::STATUS_CANCELED  => 'default',
    ];

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'deadline'];
    protected $appends = ['status_name'];
    protected $attributes = ['status'  => self::STATUS_ACTIVE];

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
            $model->user_id = request()->user()->id;
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();

            if ($model->status == self::STATUS_CLOSED) {
                $model->closed_user_id = request()->user()->id;
            }
        });
    }

    ### GETTERS ###

    public function getStatusNameAttribute(): string
    {
        return __("message.dispute.statuses.$this->status");
    }

    ### LINKS ###

    public function problem(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(DisputeProblem::class, 'problem_id')
            ->select(['id', "name_{$lang} as name", 'days'])
            ->withDefault();
    }

    public function dispute_closed_reason(): BelongsTo
    {
        return $this->belongsTo(DisputeClosedReason::class, 'dispute_closed_reason_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function closed_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_user_id');
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(Rate::class, 'rate_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function admin_user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'admin_user_id');
    }

    public function track(): HasOne
    {
        return $this->hasOne(Track::class);
    }

    ### SCOPES ###

    /**
     * Активные чаты.
     *
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('disputes.status', self::STATUS_ACTIVE);
    }

    /**
     * Назначенные споры.
     *
     * @param $query
     * @return mixed
     */
    public function scopeAppointed($query)
    {
        return $query->where('disputes.status', self::STATUS_APPOINTED);
    }

    /**
     * Споры в работе.
     *
     * @param $query
     * @return mixed
     */
    public function scopeInWork($query)
    {
        return $query->where('disputes.status', self::STATUS_IN_WORK);
    }

    /**
     * Закрытые чаты.
     *
     * @param $query
     * @return mixed
     */
    public function scopeClosed($query)
    {
        return $query->where('disputes.status', self::STATUS_CLOSED);
    }

    /**
     * Проверка существует ли для чата спор.
     *
     * @param $query
     * @param int $chat_id
     * @return mixed
     */
    public function scopeExistsForChat($query, int $chat_id)
    {
        return $query->whereChatId($chat_id)->exists();
    }
}
