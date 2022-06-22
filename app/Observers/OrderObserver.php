<?php

namespace App\Observers;

use App\Jobs\OrderDeductionJob;
use App\Models\Order;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class OrderObserver
{
    public function creating(Order $order)
    {
        $order->slug = Str::slug($order->name) . '-'. Str::random(8);
        $order->price_usd = convertPriceToUsd($order->price * $order->products_count, $order->currency);
        $order->user_price_usd = convertPriceToUsd($order->user_price, $order->user_currency);
        $order->created_at = Date::now()->toDateTimeString();
        $order->register_date = Date::now()->format('Y-m-d');
    }

    public function created(Order $order)
    {
        # после создания заказа рассчитываем по нему налоги и комиссии
        OrderDeductionJob::dispatch($order);
    }

    public function updating(Order $order)
    {
        $order->price_usd = convertPriceToUsd($order->price * $order->products_count, $order->currency);
        $order->user_price_usd = convertPriceToUsd($order->user_price, $order->user_currency);
        $order->updated_at = Date::now()->toDateTimeString();
    }

    public function updated(Order $order)
    {
        # если по заказу были изменения полей, которые влияют на общую стоимость заказа, то пересчитываем налоги и комиссии
        if (
               $order->price != $order->getOriginal('price')
            || $order->currency != $order->getOriginal('currency')
            || $order->products_count != $order->getOriginal('products_count')
        ) {
            OrderDeductionJob::dispatch($order, true);
        }
    }
}
