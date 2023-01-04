<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\Order;
use App\Models\Rate;
use App\Models\Route;
use App\Models\User;
use App\Observers\MessageObserver;
use App\Observers\OrderObserver;
use App\Observers\RateObserver;
use App\Observers\RouteObserver;
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
