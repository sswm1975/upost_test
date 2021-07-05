<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    const TYPE_PRODUCT_CONFIRMATION = 'product_confirmation';

    // Disable timestamps
    public $timestamps = false;

    // Message model
    protected $fillable = [
        'chat_id',
        'rate_id',
        'order_id',
        'from_user',
        'to_user',
        'message_date',
        'message_text',
        'message_attach',
        'type'
    ];

    protected $casts = [
        'message_attach' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->message_date = Carbon::now()->format('d.m.Y H:i');
            $model->from_user = request()->user()->user_id;
        });
    }
}
