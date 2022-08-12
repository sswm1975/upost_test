<?php

namespace App\Jobs;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Закрыть просроченные заказы.
 *
 * Условия: отбираются все активные заказы (status=active), у которых дата дедлайна меньше текущей даты (deadline < DATE(NOW())).
 */
class CloseExpiredOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/close_expired_orders.log';

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        Log::channel('single')->info("Запуск...");

        # узнаем коды просроченных заказов
        $ids = $this->getExpiredOrdersIds();

        # если пусто, то выходим
        if (empty($count = $ids->count())) {
            Log::channel('single')->info("Нет просроченных заказов.");
            return;
        }

        # закрываем все просроченные заказы
        Order::whereKey($ids)->update(['status' => Order::STATUS_CLOSED]);

        Log::channel('single')->info(sprintf("- закрыто заказов: %d (ids = %s)", $count, $ids->implode(',')));
        Log::channel('single')->info("Выполнено");
    }

    /**
     * Получить коды просроченных заказов.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getExpiredOrdersIds(): \Illuminate\Support\Collection
    {
        return Order::query()
            ->active()
            ->withoutAppends()
            ->where('deadline', '<', Carbon::today()->toDateString())
            ->pluck('id');
    }
}
