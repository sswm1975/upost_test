<?php

namespace App\Observers;

use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Rate;

class RateObserver
{
    /**
     * Обработчик события "Ставка создано".
     *
     * @param  Rate  $rate
     * @return void
     */
    public function created(Rate $rate)
    {
        # если неактивно уведомление "Появилась новая ставка", то выходим
        if (!active_notice_type($notice_type = NoticeType::NEW_RATE)) return;

        #  к ставке добавляем доп.данные
        $rate->load([
            'order' => function ($query) {
                $query->withoutAppends()->select(['id', 'user_id', 'name']);
            },
        ]);

        # создаем уведомление
        Notice::create([
            'user_id'     => $rate->order->user_id,
            'notice_type' => $notice_type,
            'object_id'   => $rate->order->id,
            'data'        => ['order_name' => $rate->order->name],
        ]);
    }
}
