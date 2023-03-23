<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Закрыть просроченные заказы.
 *
 * Условие: Отбираются все активные заказы (status=active), у которых дата дедлайна меньше текущей даты (deadline < CURDATE()).
 *
 * Результат: Найденные заказы переводится в статус closed.
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

        # узнаем коды просроченных заказов
        $ids = $this->getExpiredOrdersIds();

        # если пусто, то выходим
        if (empty($count = $ids->count())) {
            Log::channel('single')->info('Нет просроченных заказов.');
            return;
        }

        # закрываем все просроченные заказы
        Order::whereKey($ids)->update(['status' => Order::STATUS_CLOSED]);

        # закрываем чаты по закрытым заказом
        Chat::active()
            ->whereHas('order', function ($query) {
                $query->where('status', Order::STATUS_CLOSED);
            })
            ->update(['status' => Chat::STATUS_CLOSED]);

        # логируем
        Log::channel('single')->info(
            sprintf(
                'Эакрыто заказов: %d (ids = %s)',
                $count,
                $ids->implode(',')
            )
        );
    }

    /**
     * Получить коды просроченных заказов.
     * Условия:
     * - статус заказа активный
     * - дата дедлайна меньше текущей даты
     *
     * @return \Illuminate\Support\Collection
     */
    private function getExpiredOrdersIds(): \Illuminate\Support\Collection
    {
        $today = Carbon::today()->toDateString();

        return Order::withoutAppends()
            ->active()
            ->where('deadline', '<', $today)
            ->pluck('id');
    }
}
