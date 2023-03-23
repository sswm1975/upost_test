<?php

namespace App\Jobs;

use App\Models\Notice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Удалить просроченные уведомления.
 *
 * Условие: Уведомления старше 7 дней являются просроченными.
 *
 * Результат: Уведомления удаляются безвозвратно.
 */
class CloseExpiredNotices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     *  Кол-во дней, после которых считать устаревшими уведомления
     * @var int
     */
    const DAYS = 7;

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

        $expired = Carbon::now()->subDays(static::DAYS)->toDateTimeString();
        $count = Notice::where('created_at', '<', $expired)->delete();

        $text = $count ? "Удалено уведомлений: $count" : 'Нет просроченных уведомлений.';
        Log::channel('single')->info($text);
    }
}
