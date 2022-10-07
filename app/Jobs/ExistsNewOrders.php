<?php

namespace App\Jobs;

use App\Models\Notice;
use App\Models\NoticeType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Отправить Путешественнику уведомление "Существуют новые заказы по маршруту".
 *
 * Условие: Отбираются новые заказы, которые подходят под маршрут Путешественника.
 *
 * Результат: В таблицу notice добавляются записи.
 */
class ExistsNewOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/exists_new_orders.log';

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        if (!active_notice_type($notice_type = NoticeType::EXISTS_NEW_ORDERS)) return;

        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        $rows = $this->getData();

        if (empty($count = $rows->count())) {
            Log::channel('single')->info('Нет данных.');
            return;
        }

        foreach ($rows as $row) {
            Notice::create([
                'notice_type' => $notice_type,
                'user_id'     => $row->user_id,
                'object_id'   => $row->route_id,
                'data'        => collect($row)->except('user_id'),
            ]);
        }

        Log::channel('single')->info(
            sprintf(
                'Всего отправлено уведомлений: %d (коды пользователей: %s)',
                $count,
                $rows->keyBy('user_id')->keys()->implode(',')
            )
        );
    }

    /**
     * В разрезе Путешественник и Маршрут получить кол-во новых заказов.
     * Условия:
     * - дата дедлайна маршрута не наступила;
     * - статус марщрута и заказа 'active';
     * - дата дедлайна маршрута находится между датами создания и окончания заказа;
     * - страны откуда и куда маршрута и заказа совпадают;
     * - города откуда и куда маршрута и заказа частично совпадают;
     * - дата создания заказа больше, чем дата последнего просмотра заказов.
     *
     * @return Collection
     */
    public function getData(): Collection
    {
        $rows = DB::select("
            SELECT routes.user_id, routes.id AS route_id, COUNT(orders.id) AS orders_cnt
            FROM routes, orders
            WHERE routes.deadline >= CURDATE()
            AND routes.`status` = 'active'
            AND orders.`status` = 'active'
            AND routes.deadline BETWEEN orders.register_date AND orders.deadline
            AND orders.from_country_id = routes.from_country_id
            AND orders.to_country_id = routes.to_country_id
            AND (IFNULL(orders.from_city_id, 0) = IFNULL(routes.from_city_id, 0) OR orders.from_city_id IS NULL AND routes.from_city_id > 0 OR routes.from_city_id IS NULL AND orders.from_city_id > 0)
            AND (IFNULL(orders.to_city_id, 0) = IFNULL(routes.to_city_id, 0) OR orders.to_city_id IS NULL AND routes.to_city_id > 0 OR routes.to_city_id IS NULL AND orders.to_city_id > 0)
            AND orders.created_at > IFNULL(routes.viewed_orders_at, '1900-01-01 00:00:00')
            AND NOT EXISTS (
                SELECT 1 FROM rates
                WHERE rates.route_id = routes.id AND rates.order_id = orders.id
            )
            GROUP BY routes.user_id, routes.id
        ");

        return collect($rows);
    }
}
