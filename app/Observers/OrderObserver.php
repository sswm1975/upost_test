<?php

namespace App\Observers;

use App\Jobs\OrderDeductionJob;
use App\Models\Action;
use App\Models\Order;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class OrderObserver
{
    public function creating(Order $order)
    {
        $order->slug = Str::slug($order->name) . '-'. Str::random(8);
        $order->price_usd = convertPriceToUsd($order->price, $order->currency);
        $order->register_date = Date::now()->toDateString();
    }

    public function created(Order $order)
    {
        # после создания заказа рассчитываем по нему налоги и комиссии
        OrderDeductionJob::dispatch($order);

        # добавляем действие
        $this->addAction($order, Action::ORDER_CREATED);
    }

    public function updating(Order $order)
    {
        # если изменилась цена или валюта товара, то пересчитываем цену в долларах
        if ($order->isDirty(['price', 'currency'])) {
            $order->price_usd = convertPriceToUsd($order->price, $order->currency);
        }
    }

    public function updated(Order $order)
    {
        # если по заказу были изменения полей, которые влияют на общую стоимость заказа, то пересчитываем налоги и комиссии
        if ($order->wasChanged(['price_usd', 'currency', 'products_count'])) {
            OrderDeductionJob::dispatch($order, true);
        }

        # изменено кол-во просмотров
        if ($order->wasChanged('looks')) {
            $this->addAction($order, Action::ORDER_LOOKS_CHANGED);
        }

        # изменено кол-во жалоб
        if ($order->wasChanged('strikes')) {
            $this->addAction($order, Action::ORDER_STRIKE_CHANGED);
        }

        # изменился статус заказа
        if ($order->wasChanged(['status'])) {
            # заказ забаненный
            if ($order->status == Order::STATUS_BANNED) {
                $this->addAction($order, Action::ORDER_BANNED);
            # остальные статусы
            } else {
                $this->addAction($order, Action::ORDER_STATUS_CHANGED);
            }
        }

        # изменены данные заказа
        if ($order->wasChanged([
            'name', 'product_link', 'price', 'currency', 'products_count', 'description', 'images',
            'from_country_id', 'from_city_id', 'to_country_id', 'to_city_id', 'wait_range_id',
            'user_price_usd', 'not_more_price'
        ])) {
            $this->addAction($order, Action::ORDER_UPDATES);
        }
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param Order $order
     * @return void
     */
    public function deleted(Order $order)
    {
        $this->addAction($order,Action::ORDER_DELETED);
    }

    /**
     * Handle the user "force deleted" event.
     *
     * @param Order $order
     * @return void
     */
    public function forceDeleted(Order $order)
    {
        $this->addAction($order,Action::ORDER_DELETED);
    }

    /**
     * Handle the user "restored" event.
     *
     * @param Order $order
     * @return void
     */
    public function restored(Order $order)
    {
        $this->addAction($order,Action::ORDER_RESTORED);
    }

    /**
     * Add action for user's model.
     *
     * @param Order $order
     * @param string $name
     */
    private function addAction(Order $order, string $name)
    {
        $auth_user_id = request()->user()->id ?? 0;

        Action::create([
            'user_id'  => $order->user_id,
            'is_owner' => $auth_user_id == $order->user_id,
            'name'     => $name,
            'changed'  => $order->getChanges(),
            'data'     => $order,
        ]);
    }
}
