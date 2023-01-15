<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Пересчет долларового эквивалента по заказам.
 *
 * Задание актуально после смены курсов валют.
 * Действия: обновляет долларовый эквивалент цены (price_usd) по активным заказам
 * (если стоимость заказа в долларах изменится, то выполнится пересчет всех налогов и комиссий по заказу - подхватит
 * наблюдатель OrderObserver).
 */
class RecalcAmountInUSD implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/recalc_amount_in_usd.log';

    /**
     * Количество обрабатываемых заказов в блоке.
     *
     * @var int
     */
    const ORDERS_CHUNK_COUNT = 50;

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        Log::channel('single')->info("Запуск...");

        static::updateAmountUSDInOrders();

        Log::channel('single')->info("Выполнено");
    }

    /**
     * Обновить долларовый эквивалент цены товара по активным заказам.
     * (если стоимость заказа в долларах изменится, то выполнится пересчет всех налогов и комиссий по заказу - подхватит наблюдатель OrderObserver).
     *
     * @return void
     */
    public static function updateAmountUSDInOrders()
    {
        Log::channel('single')->info("Заказы:");

        Order::query()
            ->active()
            ->withoutAppends()
            ->withoutRelations()
            ->chunk(self::ORDERS_CHUNK_COUNT, function($orders) {
                $updated = 0;
                foreach ($orders as $order) {
                    $order->price_usd = convertPriceToUsd($order->price, $order->currency);
                    $order->timestamps = false;
                    if ($order->isDirty()) $updated +=1;
                    $order->save();
                }
                Log::channel('single')->info(sprintf("- обновлено записей: %d из %d", $updated, count($orders)));
            });
    }
}
