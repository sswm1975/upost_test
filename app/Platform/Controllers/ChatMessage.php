<?php

namespace App\Platform\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;

class ChatMessage implements Renderable
{
    public function render($id = null)
    {
        $chat = Chat::selectRaw("
                chats.id,
                chats.status,
                chats.created_at,
                chats.updated_at,
                chats.lock_status,

                IFNULL((SELECT COUNT(1) FROM messages WHERE chat_id = chats.id), 0) AS messages_cnt,

                chats.customer_id,
                TRIM(CONCAT(uc.`surname`, ' ', uc.`name`)) AS customer_name,
                uc.phone AS customer_phone,
                uc.email AS customer_email,
                uc.photo AS customer_photo,
                chats.customer_unread_count,

                chats.order_id,
                o.`name` AS order_name,
                o.`description` AS order_description,
                o.product_link AS order_url,
                o.price AS order_price,
                o.currency AS order_currency,
                o.products_count AS order_products_count,
                o.user_price AS order_profit_price,
                o.user_currency AS order_profit_currency,
                o.deadline AS order_deadline,
                o.images AS order_images,

                chats.performer_id,
                TRIM(CONCAT(up.`surname`, ' ', up.`name`)) AS performer_name,
                up.phone AS performer_phone,
                up.email AS performer_email,
                up.photo AS performer_photo,
                chats.performer_unread_count,

                chats.route_id,
                cntfr.name_ru AS route_from_country,
                cfr.name_ru AS route_from_city,
                cnttr.name_ru AS route_to_country,
                ctr.name_ru AS route_to_city,
                r.deadline AS route_deadline,

                EXISTS (SELECT 1 FROM disputes WHERE chat_id = chats.id) AS exists_dispute
            ")
        ->join('users as uc','uc.id', 'chats.customer_id')
        ->join('users as up','up.id', 'chats.performer_id')
        ->join('orders AS o','o.id', 'chats.order_id')
        ->join('routes AS r','r.id', 'chats.route_id')
        ->join('countries AS cntfr','cntfr.id', 'r.from_country_id')
        ->join('countries AS cnttr','cnttr.id', 'r.to_country_id')
        ->leftJoin('cities AS cfr','cfr.id', 'r.from_city_id')
        ->leftJoin('cities AS ctr','ctr.id', 'r.to_city_id')
        ->find($id);

        if (empty($chat)) {
            return [];
        }

        $messages = Message::with('user:id,name,surname,photo,role')
            ->addSelect(DB::raw('*, (select 1 from disputes where message_id = messages.id) AS is_dispute_message'))
            ->where('chat_id', $id)
            ->get();

        $title = '<i class="fa fa-commenting"></i> Переписка по чату № ' . $chat->id;

        $content = view('platform.chats.modal_body')
            ->with('chat', $chat)
            ->with('messages', $messages)
            ->render();

        $footer = view('platform.chats.modal_footer')
            ->with('chat_id', $chat->id)
            ->render();

        return compact('title', 'content', 'footer');
    }
}
