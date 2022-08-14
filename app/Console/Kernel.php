<?php

namespace App\Console;

use App\Jobs\CloseExpiredOrders;
use App\Jobs\CloseExpiredRate;
use App\Jobs\RecalcAmountInUSD;
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
            ->description('Отправка писем пользователям, у которых сегодня дедлайн по ставке')
            ->dailyAt('00:01')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path('logs/sendmail_deadline-rate.log'));

        $schedule->job(new CloseExpiredRate)
            ->description('Закрыть просроченные ставки')
            ->dailyAt('00:03')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(CloseExpiredRate::LOG_FILE));;

        $schedule->job(new CloseExpiredOrders)
            ->description('Закрыть просроченные заказы')
            ->dailyAt('0:05')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(CloseExpiredOrders::LOG_FILE));


        $schedule->command('fill:currency_rates')
            ->description('Обновить курсы валют (сервис fixer.io)')
            ->dailyAt('08:00')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path('logs/fill_currency_rates.log'));

        $schedule->job(new RecalcAmountInUSD)
            ->description('Пересчет долларового эквивалента по заказам и ставкам')
            ->dailyAt('08:10')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(RecalcAmountInUSD::LOG_FILE));
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
