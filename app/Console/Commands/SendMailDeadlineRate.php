<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DeadlineRate;

class SendMailDeadlineRate extends Command
{
    /**
     * Имя и подпись консольной команды.
     *
     * @var string
     */
    protected $signature = 'sendmail:deadline-rate';

    /**
     * Описание консольной программы.
     *
     * @var string
     */
    protected $description = 'Отправка писем пользователям, у которых сегодня дедлайн по ставке';

    /**
     * Создание экземпляра команды.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Выполнить консольную команду.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Отправляем письма пользователям, у которых сегодня дедлайн по ставке...');

        $users = $this->getUsersWithTodayDeadlineRate();

        Notification::send($users, new DeadlineRate);

        $this->info('Отправлено писем: ' . $users->count());
        $this->info('');
    }

    /**
     * Получить список пользователей, у которых сегодня дедлайн по ставке со статусом active.
     *
     * @return User[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    private function getUsersWithTodayDeadlineRate()
    {
        return User::query()
            ->withoutAppends()
            ->has('ratesDeadlineToday')
            ->get(['id', 'email', 'name', 'surname']);
    }
}
