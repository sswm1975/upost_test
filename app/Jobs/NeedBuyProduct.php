<?php

namespace App\Jobs;

use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Rate;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Отправить Путешественнику уведомление "Купите товар по заказу, который вы доставляете".
 *
 * Условие: Отбираются подтвержденные ставки, с даты оплаты которых прошло больше 10 часов.
 *
 * Результат: В таблицу notice добавляются записи.
 */
class NeedBuyProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/notices/need_buy_product.log';

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        if (!active_notice_type($notice_type = NoticeType::NEED_BUY_PRODUCT)) return;

        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        $rows = $this->getData();

        if (empty($count = $rows->count())) {
            Log::channel('single')->info('Нет данных');
            return;
        }

        foreach ($rows as $row) {
            Notice::create([
                'notice_type' => $notice_type,
                'user_id'     => $row->user_id,
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
     * Получить подтвержденные ставки, с даты оплаты которых прошло больше 10 часов.
     * Условия:
     * - статус ставки 'accepted'
     * - с даты создания транзакции по ставке прошло больше 10 часов
     *
     * @return \Illuminate\Support\Collection
     */
    private function getData(): \Illuminate\Support\Collection
    {
        return Rate::withoutAppends()
            ->join('orders', 'orders.id', 'rates.order_id')
            ->where('rates.status', Rate::STATUS_ACCEPTED)
            ->whereHas('transaction', function($query) {
                $now = Carbon::now()->toDateTimeString();
                $query->whereRaw("HOUR(TIMEDIFF('{$now}', created_at)) >= 10");
            })
            ->get(['rates.id AS rate_id', 'rates.user_id', 'orders.id AS order_id', 'orders.name AS order_name']);
    }
}
