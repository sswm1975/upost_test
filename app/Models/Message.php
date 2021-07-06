<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
