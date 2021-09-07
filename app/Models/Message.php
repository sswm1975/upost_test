<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Message
 *
 * @property int $message_id Код
 * @property int $chat_id Код чата
 * @property int $rate_id Код ставки
 * @property int $order_id Код заказа
 * @property int $from_user Код пользователя "От кого"
 * @property int $to_user Код пользователя "Кому"
 * @property string $message_date Дата сообщения
 * @property string|null $message_text Текст сообщения
 * @property array|null $message_attach Прикрепленные файлы
 * @property string $type Тип сообщения
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereFromUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereMessageAttach($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereMessageDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereMessageText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereToUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereType($value)
 * @mixin \Eloquent
 */
class Message extends Model
{
    const TYPE_SIMPLE = 'simple';
    const TYPE_ACCEPTING = 'accepting';
    const TYPE_PRODUCT_CONFIRMATION = 'product_confirmation';

    const TYPES = [
        self::TYPE_SIMPLE,
        self::TYPE_ACCEPTING,
        self::TYPE_PRODUCT_CONFIRMATION,
    ];

    public $timestamps = false;

    protected $fillable = [
        'chat_id',
        'rate_id',
        'order_id',
        'from_user',
        'to_user',
        'message_date',
        'message_text',
        'message_attach',
        'type',
    ];

    protected $casts = [
        'message_attach' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->message_date = $model->freshTimestamp();
            $model->from_user = request()->user()->user_id;
        });
    }
}
