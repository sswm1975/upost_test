<?php

namespace App\Jobs;

use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Закрыть просроченные маршруты.
 *
 * Условие: Отбираются все активные маршруты (status=active), у которых дата дедлайна меньше текущей даты (deadline < CURDATE()).
 *
 * Результат: Найденные маршруты переводится в статус closed.
 */
class CloseExpiredRoutes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/close_expired_routes.log';

    /**
     * Получить коды просроченных маршрутов.
     * Условия:
     * - статус маршрута активный
     * - дата дедлайна меньше текущей даты
     * - все связанные ставки находятся в завершенном статусе
     *
     * @return \Illuminate\Support\Collection
     */
    private function getExpiredRoutesIds(): \Illuminate\Support\Collection
    {
        $today = Carbon::today()->toDateString();

        return Route::withoutAppends()
            ->active()
            ->where('deadline', '<', $today)
            ->whereDoesntHave('rates', function($query) {
                return $query->whereNotIn('status', ['successful', 'done', 'failed', 'banned']);
            })
            ->pluck('id');
    }

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        # узнаем коды просроченных маршрутов
        $ids = $this->getExpiredRoutesIds();

        # если пусто, то выходим
        if (empty($count = $ids->count())) {
            Log::channel('single')->info('Нет просроченных маршрутов.');
            return;
        }

        # закрываем все просроченные заказы
        Route::whereKey($ids)->update(['status' => Route::STATUS_CLOSED]);

        # логируем
        Log::channel('single')->info(
            sprintf(
                'Эакрыто маршрутов: %d (ids = %s)',
                $count,
                $ids->implode(',')
            )
        );
    }
}
