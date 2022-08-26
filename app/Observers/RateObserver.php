<?php

namespace App\Observers;

use App\Events\MessageNewRate;
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
        $rate->load([
            'user' => function ($query) {
                $query->select(User::FIELDS_FOR_SHOW);
            },
            'order' => function ($query) {
                $query->withoutAppends()->select(['id', 'user_id']);
            },
        ]);

        try {
            broadcast(new MessageNewRate($rate))->toOthers();
        } catch (\Exception $e) {

        }
    }
}
