<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Track
 *
 * @property int $id Код
 * @property string $ttn ТТН
 * @property int|null $dispute_id Код спора
 * @property string $status Статус
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read \App\Models\Dispute|null $dispute
 * @method static \Illuminate\Database\Eloquent\Builder|Track newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Track newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Track query()
 * @method static \Illuminate\Database\Eloquent\Builder|Track whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Track whereDisputeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Track whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Track whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Track whereTtn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Track whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Track extends Model
{
    use TimestampSerializable;

    const STATUS_NEW = 'new';
    const STATUS_SENT = 'sent';
    const STATUS_RECEIVED = 'received';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';
    const STATUS_CLOSED = 'closed';

    const STATUSES = [
        self::STATUS_NEW      => 'Новый',
        self::STATUS_SENT     => 'Отправленный',
        self::STATUS_RECEIVED => 'Полученный',
        self::STATUS_VERIFIED => 'Проверен',
        self::STATUS_FAILED   => 'Проблемный',
        self::STATUS_CLOSED   => 'Закрытый',
    ];

    const STATUS_COLORS = [
        self::STATUS_NEW      => 'info',
        self::STATUS_SENT     => 'primary',
        self::STATUS_RECEIVED => 'warning',
        self::STATUS_VERIFIED => 'success',
        self::STATUS_FAILED   => 'danger',
        self::STATUS_CLOSED   => 'default',
    ];

    protected $attributes = [
        'status'  => self::STATUS_NEW,
    ];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }
}
