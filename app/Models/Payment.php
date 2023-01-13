<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Payment
 *
 * @property int $id Код
 * @property int $user_id Пользователь
 * @property int $rate_id Код ставки
 * @property int $order_id Код заказа
 * @property string $amount Сумма
 * @property string|null $type Тип платежа
 * @property string $description Описание
 * @property int|null $admin_user_id Менеджер, выполнивший платеж
 * @property string $status Статус
 * @property string|null $created_at Добавлено
 * @property string|null $updated_at Изменено
 * @property-read Administrator|null $admin_user
 * @property-read string $status_name
 * @property-read string $type_name
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAdminUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUserId($value)
 * @mixin \Eloquent
 */
class Payment extends Model
{
    use TimestampSerializable;

    const STATUS_NEW  = 'new';
    const STATUS_APPOINTED  = 'appointed';
    const STATUS_DONE = 'done';
    const STATUS_REJECTED  = 'rejected';

    const STATUSES = [
        self::STATUS_NEW       => 'Новая',
        self::STATUS_APPOINTED => 'Назначенная',
        self::STATUS_DONE      => 'Выполненная',
        self::STATUS_REJECTED  => 'Отклоненная',
    ];

    const STATUS_COLORS = [
        self::STATUS_NEW       => 'danger',
        self::STATUS_APPOINTED => 'warning',
        self::STATUS_DONE      => 'success',
        self::STATUS_REJECTED  => 'default',
    ];

    /**
     * Типы платежей.
     */
    const TYPE_REWARD = 'reward';  # вознаграждение
    const TYPE_REFUND = 'refund';  # возврат средств
    const TYPES = [
        self::TYPE_REWARD => 'Вознаграждение',
        self::TYPE_REFUND => 'Возврат средств',
    ];

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $attributes = ['status' => self::STATUS_NEW];
    protected $appends = ['status_name', 'type_name'];

    ### GETTERS ###

    public function getStatusNameAttribute(): string
    {
        return __("message.payment.statuses.$this->status");
    }

    public function getTypeNameAttribute(): string
    {
        return __("message.payment.types.$this->type");
    }

    ### LINKS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function admin_user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'admin_user_id');
    }
}
