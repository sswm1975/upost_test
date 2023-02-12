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
        $chat = Chat::find($message->chat_id);

        # для статуса блокировки
        $extra = [];

        # увеличиваем счетчик непрочитанных сообщений и броадкастим его
        if (empty($auth_user_id) || $auth_user_id == $chat->performer_id) {
            if ($auth_user_id == $chat->performer_id) {
                # исполнителю было разрешено одно сообщение - меняем на блокировку всем
                if ($chat->lock_status == Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_PERFORMER) {
                    $extra = ['lock_status' => Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL];
                }
                # всем было разрешено одно сообщение - меняем на разрешено заказчику одно сообщение
                if ($chat->lock_status == Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ALL) {
                    $extra = ['lock_status' => Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_CUSTOMER];
                }
            }
            $chat->increment('customer_unread_count', 1, $extra);
            Chat::broadcastCountUnreadMessages($chat->customer_id);
        }
        if (empty($auth_user_id) || $auth_user_id == $chat->customer_id) {
            if ($auth_user_id == $chat->customer_id) {
                # заказчику было разрешено одно сообщение - меняем на блокировку всем
                if ($chat->lock_status == Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_CUSTOMER) {
                    $extra = ['lock_status' => Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL];
                }
                # всем было разрешено одно сообщение - меняем на разрешено исполнителю одно сообщение
                if ($chat->lock_status == Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ALL) {
                    $extra = ['lock_status' => Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_PERFORMER];
                }
            }
            $chat->increment('performer_unread_count', 1, $extra);
            Chat::broadcastCountUnreadMessages($chat->performer_id);
        }

        # если существует спор, то увеличиваем счетчик непрочитанных сообщений менеджером спора
        if (!empty($chat->dispute)) {
            $chat->dispute->increment('unread_messages_count');
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
            'name'     => $recipient_id == $message->user_id ? Action::MESSAGE_CREATED : Action::MESSAGE_RECEIVED,
            'changed'  => $message->getChanges(),
            'data'     => $message,
        ]);
    }
}
