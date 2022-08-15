<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\DeadlineRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Задание: Отправка писем пользователям, у которых сегодня дедлайн по ставке.
 *
 * Условие: Отбираются пользователи, у которых дата дедлайна активной ставки равна текущей дате (deadline = CURDATE()).
 *
 * Результат: Рассылка писем.
 */
class SendMailDeadlineRate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/sendmail_deadline-rate.log';

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        # узнаем список пользователей
        $users = $this->getUsersWithTodayDeadlineRate();

        # если пусто, то выходим
        if (empty($users)) {
            Log::channel('single')->info('Нет ставок, у которых сегодня дедлайн.');
            return;
        }

        # рассылаем письма
        Notification::send($users, new DeadlineRate);

        # логируем
        Log::channel('single')->info(
            sprintf(
                'Отправлено писем: %d',
                $users->count()
            )
        );
    }

    /**
     * Получить список пользователей, у которых сегодня дедлайн по ставке со статусом active.
     *
     * @return User[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    private function getUsersWithTodayDeadlineRate()
    {
        return User::withoutAppends()
            ->has('ratesDeadlineToday')
            ->get(['id', 'email']);
    }
}
