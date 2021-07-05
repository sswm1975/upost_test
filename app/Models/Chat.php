<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
