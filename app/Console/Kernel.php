<?php

namespace App\Console;

use App\Jobs\CloseExpiredOrders;
use App\Jobs\CloseExpiredRate;
use App\Jobs\CloseExpiredRoutes;
use App\Jobs\NeedBuyProduct;
use App\Jobs\RecalcAmountInUSD;
use App\Jobs\ReviewForCustomer;
use App\Jobs\ReviewForTraveler;
use App\Jobs\SelectTraveler;
use App\Jobs\SendMailDeadlineRate;
use App\Jobs\SoonExpiredOrders;
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
        $schedule->job(new SendMailDeadlineRate)
            ->description('Отправка писем пользователям, у которых сегодня дедлайн по ставке')
            ->dailyAt('00:01')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(SendMailDeadlineRate::LOG_FILE));

        $schedule->job(new CloseExpiredRate)
            ->description('Закрыть просроченные ставки')
            ->dailyAt('00:03')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(CloseExpiredRate::LOG_FILE));

        $schedule->job(new CloseExpiredOrders)
            ->description('Закрыть просроченные заказы')
            ->dailyAt('0:05')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(CloseExpiredOrders::LOG_FILE));

        $schedule->job(new CloseExpiredRoutes)
            ->description('Закрыть просроченные маршруты')
            ->dailyAt('0:07')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(CloseExpiredRoutes::LOG_FILE));

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

        # расписания для уведомлений
        $this->scheduleNotices($schedule);
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

    private function scheduleNotices(Schedule $schedule)
    {
        $schedule->job(new SoonExpiredOrders)
            ->description('Отправить уведомление "Скоро крайний срок заказа"')
            ->dailyAt('0:06')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(SoonExpiredOrders::LOG_FILE));

        $schedule->job(new SelectTraveler)
            ->description('Отправить Заказчику уведомление "Выберите Путешественника"')
            ->dailyAt('0:06')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(SelectTraveler::LOG_FILE));

        $schedule->job(new ReviewForTraveler)
            ->description('Отправить Заказчику уведомление "Оставьте отзыв для Путешественника"')
            ->dailyAt('0:06')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(ReviewForTraveler::LOG_FILE));

        $schedule->job(new ReviewForCustomer)
            ->description('Отправить Путешественнику уведомление "Оставьте отзыв для Заказчику"')
            ->dailyAt('0:06')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(ReviewForCustomer::LOG_FILE));

        $schedule->job(new NeedBuyProduct)
            ->description('Отправить Путешественнику уведомление "Купите товар по заказу, который вы доставляете"')
            ->dailyAt('0:06')
            ->timezone('Europe/Kiev')
            ->appendOutputTo(storage_path(NeedBuyProduct::LOG_FILE));
    }
}
