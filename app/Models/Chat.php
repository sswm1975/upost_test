<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Chat
 *
 * @property int $id Код чата
 * @property int $rate_id Код ставки
 * @property int $order_id Код заказа
 * @property int $user_id Код пользователя заказчика
 * @property int $to_user Код пользователя, который сделал ставку
 * @property string $status Статус
 * @property string|null $last_sms Последнее СМС
 * @property string $created_at Дата создания
 * @property-read \App\Models\Rate $rate
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereLastSms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereToUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUserId($value)
 * @mixin \Eloquent
 */
class Chat extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUCCESSFUL = 'successful';

    public $timestamps = false;

    protected $fillable = [
        'rate_id',
        'order_id',
        'user_id',
        'to_user',
        'status',
        'last_sms',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
            $model->status = self::STATUS_ACTIVE;
            $model->last_sms = '';
        });
    }

    public function rate()
    {
        return $this->belongsTo(Rate::class, 'rate_id');
    }
}
