<?php

namespace App\Jobs;

use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Rate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Отправить Заказчику уведомление "Оставьте отзыв для путешественника".
 *
 * Условие: Отбираются успешные ставки (статусы 'successful', 'done'), по которым нет отзыва от заказчика.
 *
 * Результат: В таблицу notice добавляются записи.
 */
class ReviewForTraveler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/notices/review_for_traveler.log';

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        if (!active_notice_type($notice_type = NoticeType::REVIEW_FOR_TRAVELER)) return;

        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        $rows = $this->getData();

        if (empty($count = $rows->count())) {
            Log::channel('single')->info('Нет данных');
            return;
        }

        foreach ($rows as $row) {
            Notice::create([
                'notice_type' => $notice_type,
                'user_id'     => $row->order_user_id,
                'object_id'   => $row->order_id,
                'data'        => ['order_name' => $row->order_name, 'rate_id' => $row->rate_id],
            ]);
        }

        Log::channel('single')->info(
            sprintf(
                'Всего отправлено уведомлений: %d (rate ids = %s)',
                $count,
                $rows->pluck('rate_id')->implode(',')
            )
        );
    }

    /**
     * Получить успешные ставки, по которым нет отзыва от заказчика.
     * Условия:
     * - статус ставки 'successful' или 'done'
     * - в таблице reviews нет записи, где recipient_id (получатель отзыва) равен владельцу ставки
     *
     * @return \Illuminate\Support\Collection
     */
    private function getData(): \Illuminate\Support\Collection
    {
        return Rate::withoutAppends()
            ->join('orders', 'orders.id', 'rates.order_id')
            ->whereIn('rates.status', [Rate::STATUS_SUCCESSFUL, Rate::STATUS_DONE])
            ->whereDoesntHave('reviews', function($query) {
                $query->where('recipient_id', 'rates.user_id');
            })
            ->get(['rates.id AS rate_id', 'orders.id AS order_id', 'orders.user_id AS order_user_id', 'orders.name AS order_name']);
    }
}
