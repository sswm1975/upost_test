<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
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
}
