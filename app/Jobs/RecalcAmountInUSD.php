<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Rate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalcAmountInUSD implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const ORDERS_CHUNK_COUNT = 50;
    const RATES_CHUNK_COUNT = 50;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        config(['logging.channels.single.path' => storage_path('logs/recalc_amount_in_usd.log')]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::channel('single')->info("Запуск...");

        static::updateAmountUSDInRates();
        static::updateAmountUSDInOrders();

        Log::channel('single')->info("Выполнено");
    }

    /**
     * Обновить сумму вознаграждения в долларовом эквиваленте по активным ставкам.
     *
     * @return void
     */
    public static function updateAmountUSDInRates()
    {
        Log::channel('single')->info("Cтавки:");

        Rate::query()
            ->active()
            ->withoutAppends()
            ->chunk(self::RATES_CHUNK_COUNT, function($rates) {
                $updated = 0;
                foreach ($rates as $rate) {
                    $rate->amount_usd = convertPriceToUsd($rate->amount, $rate->currency);
                    $rate->timestamps = false;
                    if ($rate->isDirty()) $updated +=1;
                    $rate->save();
                }
                Log::channel('single')->info(sprintf("- обновлено записей: %d из %d", $updated, count($rates)));
            });
    }

    /**
     * Обновить долларовый эквивалент цены и вознаграждения по активным товарам.
     * (если стоимость товара в долларах изменится, то будет пересчет всех налогов и комиссий по товару - подхватит наблюдатель OrderObserver).
     *
     * @return void
     */
    public static function updateAmountUSDInOrders()
    {
        Log::channel('single')->info("Заказы:");

        Order::query()
            ->active()
            ->withoutAppends()
            ->chunk(self::ORDERS_CHUNK_COUNT, function($orders) {
                $updated = 0;
                foreach ($orders as $order) {
                    $order->price_usd = convertPriceToUsd($order->price, $order->currency);
                    $order->user_price_usd = convertPriceToUsd($order->user_price, $order->user_currency);
                    $order->timestamps = false;
                    if ($order->isDirty()) $updated +=1;
                    $order->save();
                }
                Log::channel('single')->info(sprintf("- обновлено записей: %d из %d", $updated, count($orders)));
            });
    }
}
