<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Mail;
use App\Events\OrderBanned as EventOrderBanned;
use App\Mail\OrderBanEmail;
use App\Models\User;

class OrderBanned
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param EventOrderBanned $event
     * @return void
     */
    public function handle(EventOrderBanned $event)
    {
        $user = User::find($event->order->user_id);

        Mail::to($user->email)->send(new OrderBanEmail($user->lang));
    }
}
