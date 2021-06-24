<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DeadlineRate;

class SendMailDeadlineRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendmail:deadline-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправка писем пользователям, у которых сегодня дедлайн по ставке';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Отправляем письма пользователям, у которых сегодня дедлайн по ставке...');

        $users = $this->getUsersWithDeadlineRate();

        Notification::send($users, new DeadlineRate());

        $this->info('Отправлено писем: ' . $users->count());
        $this->info('');

        return 0;
    }

    private function getUsersWithDeadlineRate()
    {
        return User::has('ratesDeadlineToday')->get(['user_id', 'user_email', 'user_name', 'user_surname']);
    }
}
