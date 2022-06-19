<?php

namespace App\Console;

use App\Jobs\CloseDeadlineRate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendMailDeadlineRate::class,
        Commands\FillCurrencyRates::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sendmail:deadline-rate')
            ->name('sendmail_deadline-rate')
            ->description('Отправка писем пользователям, у которых сегодня дедлайн по ставке')
            ->dailyAt('09:00')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path('logs/sendmail_deadline-rate.log'));

        $schedule->job(new CloseDeadlineRate)
            ->name('close_expired_rates')
            ->description('Закрыть просроченные ставки')
            ->dailyAt('10:00')
            ->timezone('Europe/Kiev');

        $schedule->command('fill:currency_rates')
            ->name('fill_currency_rates')
            ->description('Обновить курсы валют (сервис fixer.io)')
            ->dailyAt('08:00')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path('logs/fill_currency_rates.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
