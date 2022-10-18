<?php

namespace App\Jobs;

use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Отправить уведомление "Скоро крайний срок заказа" для Заказчика и Путешественника.
 *
 * Условие: Отбираются действующие заказы (статусы 'active' и 'in_work'), у которых дата дедлайна наступит меньше, чем через 72 часа.
 *
 * Результат: В таблицу notice добавляются записи.
 */
class SoonExpiredOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/notices/soon_expired_orders.log';

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        # если неактивно уведомление "Скоро крайний срок заказа", то выходим
        if (!active_notice_type($notice_type = NoticeType::SOON_EXPIRED_ORDER)) return;

        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        $rows = $this->getData();

        if (empty($count = $rows->count())) {
            Log::channel('single')->info('Нет данных');
            return;
        }

        foreach ($rows as $row) {
            # отправляем уведомление Заказчику
            Notice::create([
                'user_id'     => $row->order_user_id,
                'notice_type' => $notice_type,
                'object_id'   => $row->order_id,
                'data'        => ['order_name' => $row->order_name],
            ]);

            # отправляем уведомление Путешественнику, который доставляет заказ
            if (!empty($row->route_user_id)) {
                Notice::create([
                    'user_id'     => $row->route_user_id,
                    'notice_type' => $notice_type,
                    'object_id'   => $row->order_id,
                    'data'        => ['order_name' => $row->order_name],
                ]);
            }
        }

        # логируем
        Log::channel('single')->info(
            sprintf(
                'Всего отправлено уведомлений: %d (order ids = %s)',
                $count,
                $rows->pluck('order_id')->implode(',')
            )
        );
    }

    /**
     * Получить заказы, у которых скоро дедлайн
     * В ответе код заказа, код заказчика и код исполнителя, если он доставляет данный заказ.
     * Условия:
     * - статус заказа 'active' и 'in_work'
     * - разница между датой дедлайна и текущей датой меньше 72 часов
     *
     * @return \Illuminate\Support\Collection
     */
    private function getData(): \Illuminate\Support\Collection
    {
        $today = Carbon::today()->toDateString();

        return Order::withoutAppends()
            ->leftJoin('rates', 'rates.order_id', 'orders.id')
            ->whereIn('orders.status', ['active', 'in_work'])
            ->whereRaw("HOUR(TIMEDIFF(orders.deadline, '$today')) BETWEEN 0 AND 72")
            ->get(['orders.id as order_id', 'orders.user_id as order_user_id', 'rates.user_id as route_user_id', 'orders.name as order_name']);
    }
}
