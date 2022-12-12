<?php

namespace App\Platform\Controllers;

use App\Models\Chat;
use App\Models\Dispute;
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
            uc.gender AS customer_gender,
            uc.birthday AS customer_birthday,
            (select name_ru from cities where id = uc.city_id) AS customer_city,
            uc.card_number AS customer_card_number,
            uc.resume AS customer_resume,
            uc.register_date AS customer_register_date,
            chats.customer_unread_count,

            chats.order_id,
            o.`name` AS order_name,
            o.`description` AS order_description,
            o.product_link AS order_url,
            (select name_ru from countries where id = o.from_country_id) AS order_from_country,
            (select name_ru from cities where id = o.from_city_id) AS order_from_city,
            (select name_ru from countries where id = o.to_country_id) AS order_to_country,
            (select name_ru from cities where id = o.to_city_id) AS order_to_city,
            o.price AS order_price,
            o.currency AS order_currency,
            o.products_count AS order_products_count,
            o.user_price AS order_profit_price,
            o.user_currency AS order_profit_currency,
            o.status AS order_status,
            o.created_at AS order_created_at,
            o.deadline AS order_deadline,
            o.images AS order_images,

            chats.performer_id,
            TRIM(CONCAT(up.`surname`, ' ', up.`name`)) AS performer_name,
            up.phone AS performer_phone,
            up.email AS performer_email,
            up.photo AS performer_photo,
            up.gender AS performer_gender,
            up.birthday AS performer_birthday,
            (select name_ru from cities where id = up.city_id) AS performer_city,
            up.card_number AS performer_card_number,
            up.resume AS performer_resume,
            up.register_date AS performer_register_date,
            chats.performer_unread_count,

            chats.route_id,
            r.status AS route_status,
            cntfr.name_ru AS route_from_country,
            cfr.name_ru AS route_from_city,
            cnttr.name_ru AS route_to_country,
            ctr.name_ru AS route_to_city,
            r.created_at AS route_created_at,
            r.deadline AS route_deadline,

            rates.amount AS rate_amount,
            rates.currency AS rate_currency,
            rates.comment AS rate_comment,
            rates.images AS rate_images,
            rates.status AS rate_status,
            rates.created_at AS rate_created_at,
            rates.deadline AS rate_deadline,

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
        ->leftJoin('rates','rates.chat_id', 'chats.id')
        ->find($id);

        if (empty($chat)) {
            return [];
        }

        if ($chat->exists_dispute) {
            $dispute = Dispute::with('problem')
                ->where('chat_id', $chat->id)
                ->first();
        } else {
            $dispute = null;
        }

        $messages = Message::with('user:id,name,surname,photo,role')
            ->addSelect(DB::raw('*, IF(dispute_id>0, 1, 0) AS is_dispute_message'))
            ->where('chat_id', $id)
            ->get();

        $title = view('platform.chats.modal_title')
            ->with('exists_dispute', $chat->exists_dispute)
            ->render();

        $content = view('platform.chats.modal_body')
            ->with('chat', $chat)
            ->with('dispute', $dispute)
            ->with('messages', $messages)
            ->render();

        $footer = view('platform.chats.modal_footer')
            ->with('chat', $chat)
            ->render();

        $messages_cnt = count($messages);

        return compact('title', 'content', 'footer', 'messages_cnt');
    }
}
