<?php

namespace App\Providers;

use App\Models\Dispute;
use App\Models\Message;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Rate;
use App\Models\Review;
use App\Models\Route;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\DisputeObserver;
use App\Observers\MessageObserver;
use App\Observers\OrderObserver;
use App\Observers\PaymentObserver;
use App\Observers\RateObserver;
use App\Observers\ReviewObserver;
use App\Observers\RouteObserver;
use App\Observers\TransactionObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        User::observe(UserObserver::class);
        Order::observe(OrderObserver::class);
        Route::observe(RouteObserver::class);
        Rate::observe(RateObserver::class);
        Message::observe(MessageObserver::class);
        Review::observe(ReviewObserver::class);
        Dispute::observe(DisputeObserver::class);
        Transaction::observe(TransactionObserver::class);
        Payment::observe(PaymentObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
