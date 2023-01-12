<?php

namespace App\Observers;

use App\Events\MessageAdd;
use App\Models\Action;
use App\Models\Chat;
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

        $auth_user_id = request()->user()->id ?? 0;
        $chat = Chat::find($message->chat_id, ['id', 'customer_id', 'performer_id', 'customer_unread_count', 'performer_unread_count']);

        # увеличиваем счетчик непрочитанных сообщений и броадкастим его
        if (empty($auth_user_id) || $auth_user_id == $chat->performer_id) {
            $chat->increment('customer_unread_count');
            Chat::broadcastCountUnreadMessages($chat->customer_id);
        }
        if (empty($auth_user_id) || $auth_user_id == $chat->customer_id) {
            $chat->increment('performer_unread_count');
            Chat::broadcastCountUnreadMessages($chat->performer_id);
        }

        # добавляем события "Сообщение создано" и "Сообщение получено"
        $this->addAction($message, $auth_user_id, $chat->customer_id);
        $this->addAction($message, $auth_user_id, $chat->performer_id);
    }

    /**
     * Add action.
     *
     * @param Message $message
     * @param int $auth_user_id
     * @param int $recipient_id
     */
    private function addAction(Message $message, int $auth_user_id, int $recipient_id)
    {
        Action::create([
            'user_id'  => $recipient_id,
            'is_owner' => $auth_user_id == $message->user_id,
            'name'     => $auth_user_id == $message->user_id ? Action::MESSAGE_CREATED : Action::MESSAGE_RECEIVED,
            'changed'  => $message->getChanges(),
            'data'     => $message,
        ]);
    }
}
