<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Rate;

class RateObserver
{
    /**
     * Обработчик события "Ставка создана".
     *
     * @param  Rate  $rate
     * @return void
     */
    public function created(Rate $rate)
    {
        # к ставке добавляем данные заказа
        $rate->load([
            'order' => function ($query) {
                $query->withoutAppends()->select(['id', 'user_id', 'name']);
            },
        ]);

        # если активно уведомление "Появилась новая ставка", то создаем уведомление
        if (active_notice_type($notice_type = NoticeType::NEW_RATE)) {
            Notice::create([
                'user_id'     => $rate->order->user_id,
                'notice_type' => $notice_type,
                'object_id'   => $rate->order->id,
                'data'        => ['order_name' => $rate->order->name, 'rate_id' => $rate->id],
            ]);
        }

        # добавляем действие "Создана ставка"
        $this->addAction($rate, Action::RATE_CREATED);
    }

    /**
     * Обработчик события "Ставка обновлена".
     *
     * @param Rate $rate
     * @return void
     */
    public function updated(Rate $rate)
    {
        # изменился статус ставки
        if ($rate->wasChanged(['status'])) {
            $this->addAction($rate, Action::RATE_STATUS_CHANGED);
        }

        # изменены данные ставки
        if ($rate->wasChanged(['amount', 'currency', 'deadline', 'comment'])) {
            $this->addAction($rate, Action::RATE_UPDATES);
        }
    }

    /**
     * Handle the rate "deleted" event.
     *
     * @param Rate $rate
     * @return void
     */
    public function deleted(Rate $rate)
    {
        $this->addAction($rate,Action::RATE_DELETED);
    }

    /**
     * Handle the rate "restored" event.
     *
     * @param Rate $rate
     * @return void
     */
    public function restored(Rate $rate)
    {
        $this->addAction($rate,Action::RATE_RESTORED);
    }

    /**
     * Handle the rate "force deleted" event.
     *
     * @param Rate $rate
     * @return void
     */
    public function forceDeleted(Rate $rate)
    {
        $this->addAction($rate,Action::RATE_DELETED);
    }

    /**
     * Add action for rate's model.
     *
     * @param Rate $rate
     * @param string $name
     */
    private function addAction(Rate $rate, string $name)
    {
        $auth_user_id = request()->user()->id ?? 0;

        Action::create([
            'user_id'  => $rate->user_id,
            'is_owner' => $auth_user_id == $rate->user_id,
            'name'     => $name,
            'changed'  => $rate->getChanges(),
            'data'     => $rate,
        ]);
    }
}
