<?php

namespace App\Observers;

use App\Jobs\WiseResourceJob;
use App\Models\WiseEvent;

class WiseEventObserver
{
    /**
     * Handle the transaction "created" event.
     *
     * @param  WiseEvent  $event
     * @return void
     */
    public function created(WiseEvent $event)
    {
        if ($event->status == WiseEvent::STATUS_NEW) {
            WiseResourceJob::dispatch($event);
        }
    }
}
