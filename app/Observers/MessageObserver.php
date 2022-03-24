<?php

namespace App\Observers;

use App\Events\MessageAdd;
use App\Models\Message;

class MessageObserver
{
    /**
     * Обработчик события "Сообщение создано".
     *
     * @param  Message  $message
     * @return void
     */
    public function created(Message $message)
    {
        broadcast(new MessageAdd($message))->toOthers();
    }
}
