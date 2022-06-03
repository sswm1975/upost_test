<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

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
