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
 * @property string $amount Сумма
 * @property string $description Описание
 * @property int|null $admin_user_id Менеджер, выполнивший платеж
 * @property int|null $transaction_id Транзакция по обработке платежа
 * @property string $status Статус
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAdminUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTransactionId($value)
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

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $attributes = ['status'  => self::STATUS_NEW];

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
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
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
