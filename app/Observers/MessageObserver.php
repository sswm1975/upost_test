<?php

namespace App\Observers;

use App\Events\MessageAdd;
use App\Models\Action;
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
        try {
            broadcast(new MessageAdd($message))->toOthers();
        } catch (\Exception $e) {

        }

        # добавляем события "Сообщение создано" и "Сообщение получено"
        $this->addActions($message);
    }

    /**
     * Add actions.
     *
     * @param Message $message
     */
    private function addActions(Message $message)
    {
        $auth_user_id = request()->user()->id ?? 0;

        # событие "Сообщение создано" для автора сообщения
        Action::create([
            'user_id'  => $message->user_id,
            'is_owner' => $auth_user_id == $message->user_id,
            'name'     => Action::MESSAGE_CREATED,
            'changed'  => $message->getChanges(),
            'data'     => $message,
        ]);

        # событие "Сообщение получено" для собеседника чата
        Action::create([
            'user_id'  => $message->chat->interlocutor_id,
            'is_owner' => false,
            'name'     => Action::MESSAGE_RECEIVED,
            'changed'  => $message->getChanges(),
            'data'     => $message,
        ]);
    }
}
