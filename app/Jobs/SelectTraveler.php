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
 * Задание: Отправить Заказчику уведомление "Выберите путешественника".
 *
 * Условие: Отбираются активные заказы (статус 'active'), по которым есть три или более активных ставок (статус 'active'), созданных больше 24 часа назад.
 *
 * Результат: В таблицу notice добавляются записи.
 */
class SelectTraveler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/select_traveler.log';

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        # если неактивно уведомление "Выберите путешественника", то выходим
        if (!active_notice_type($notice_type = NoticeType::SELECT_TRAVELER)) return;

        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        $rows = $this->getData();

        if (empty($count = $rows->count())) {
            Log::channel('single')->info('Нет заказов, по которым нужно выбрать доставщика.');
            return;
        }

        # отправляем уведомление Заказчику
        foreach ($rows as $order_id => $user_id) {
            Notice::create([
                'notice_type' => $notice_type,
                'user_id'     => $user_id,
                'object_id'   => $order_id,
            ]);
        }

        # логируем
        Log::channel('single')->info(
            sprintf(
                'Отправлены уведомления по заказам: %d (ids = %s)',
                $count,
                $rows->keys()->implode(',')
            )
        );
    }

    /**
     * Получить активные заказы, по которым есть три и более активных ставок, созданная больше 24 часов назад.
     * Условия:
     * - статус заказа 'active'
     * - статус связанной с заказом ставки 'active'
     * - разница между текущем временем и датой создания ставки больше 24 часов
     * - количество связанных ставок по заказу равно три или более
     *
     * @return \Illuminate\Support\Collection
     */
    private function getData(): \Illuminate\Support\Collection
    {
        return Order::withoutAppends()
            ->whereHas('rates', function ($query) {
                $now = Carbon::now()->toDateTimeString();
                $query->where('status', 'active')->whereRaw("HOUR(TIMEDIFF('{$now}', created_at)) >= 24")
                    ->havingRaw('COUNT(*) >= 3');
            }, '>=', 3)
            ->where('orders.status','active')
            ->pluck('user_id', 'id');
    }
}
