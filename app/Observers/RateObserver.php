<?php

namespace App\Observers;

use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Rate;
use App\Models\User;

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
            'user' => function ($query) {
                $query->select(User::FIELDS_FOR_SHOW);
            },
            'order' => function ($query) {
                $query->withoutAppends()->select(['id', 'user_id']);
            },
        ]);

        # создаем уведомление
        Notice::create([
            'user_id'     => $rate->order->user_id,
            'notice_type' => $notice_type,
            'object_id'   => $rate->id,
            'data'        => $rate,
        ]);
    }
}
