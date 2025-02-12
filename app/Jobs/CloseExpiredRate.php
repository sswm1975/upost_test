<?php

namespace App\Jobs;

use App\Models\Rate;
use App\Models\Chat;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Закрыть просроченные ставки и связанные чаты.
 *
 * Условие: Отбираются все активные ставки (status=active), у которых дата дедлайна
 * увеличенная на X дней отсрочки меньше текущей даты (DATE_ADD(deadline INTERVAL X DAY) < CURDATE()).
 * Реализовано более оптимизировано: у которых дата дедлайна меньше текущей даты уменьшенной
 * на X дней отсрочки (deadline < DATE_SUB(CURDATE(), INTERVAL X DAY)).
 *
 * Результат: Найденные ставки переводится в статус failed, а чаты меняют статус на closed.
 */
class CloseExpiredRate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/close_expired_rates.log';

    /**
     * Количество дней отсрочки (через сколько дней автоматически закрывать ставку).
     *
     * @var int
     */
    const COUNT_DAYS_DELAY = 3;

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        # узнаем коды просроченных ставок (key) и чатов (value)
        $ids = $this->getExpiredRatesIds();

        # если пусто, то выходим
        if (empty($ids)) {
            Log::channel('single')->info("Нет просроченных ставок.");
            return;
        }

        # закрываем все просроченные заказы и связанные чаты
        Rate::whereKey(array_keys($ids))->update(['status' => Rate::STATUS_FAILED]);
        Chat::whereKey(array_filter(array_values($ids)))->update(['status' => Chat::STATUS_CLOSED]);

        # логируем
        Log::channel('single')->info(
            sprintf(
                "Закрыто ставок: %d (ids = %s)",
                count($ids),
                implode(',', array_keys($ids))
            )
        );
    }

    /**
     * Получить коды просроченных ставок.
     * Условия:
     * - статус ставки активный
     * - дата дедлайна меньше текущей даты уменьшенной на X дней отсрочки
     *
     * @return array  [Код ставки => Код связанного чата].
     */
    private function getExpiredRatesIds(): array
    {
        $date_expired = Carbon::today()->subDays(self::COUNT_DAYS_DELAY)->toDateString();

        return Rate::withoutAppends()
            ->active()
            ->where('deadline', '<', $date_expired)
            ->pluck('chat_id', 'id')
            ->toArray();
    }
}
