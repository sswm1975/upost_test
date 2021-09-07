<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Chat
 *
 * @property int $chat_id Код чата
 * @property int|null $rate_id Код ставки
 * @property int|null $order_id Код заказа
 * @property int|null $user_id Код пользователя заказчика
 * @property int|null $to_user Код пользователя, который сделал ставку
 * @property string|null $chat_date Дата
 * @property string $chat_status Статус
 * @property string|null $last_sms Последнее СМС
 * @property-read \App\Models\Rate|null $rate
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereChatDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereChatStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereLastSms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereToUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUserId($value)
 * @mixin \Eloquent
 */
class Chat extends Model
{
    public const STATUS_SUCCESSFUL = 'successful';

    // Disable timestamps
    public $timestamps = false;

    // Chat model
    protected $fillable = [
        'chat_id',
        'rate_id',
        'order_id',
        'user_id',
        'to_user',
        'chat_date',
        'chat_status',
        'last_sms',
    ];

    public function rate()
    {
        return $this->belongsTo(Rate::class, 'rate_id');
    }
}
