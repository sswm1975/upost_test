<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Message
 *
 * @property int $id Код
 * @property int $chat_id Код чата
 * @property int $rate_id Код ставки
 * @property int $order_id Код заказа
 * @property int $from_user Код пользователя "От кого"
 * @property int $to_user Код пользователя "Кому"
 * @property string $type Тип сообщения
 * @property string $text Текст сообщения
 * @property array|null $files Прикрепленные файлы
 * @property string $created_at Дата сообщения
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereFromUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereText($value)
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
        'text',
        'files',
        'type',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
            $model->from_user = request()->user()->id;
        });
    }
}
