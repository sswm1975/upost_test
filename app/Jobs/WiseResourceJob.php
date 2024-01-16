<?php

namespace App\Jobs;

use App\Models\WiseEvent;
use App\Models\WiseResource;
use App\Models\Withdrawal;
use App\Payments\Wise;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WiseResourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private WiseEvent $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(WiseEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $wise_event_id = $this->event->id;

        $resource_id = $this->event->transfer_id;
        $resource = Wise::getTransfer($resource_id);
        $amount = $resource->sourceValue ?? 0;
        $this->addResource($wise_event_id, WiseResource::TYPE_TRANSFER, $resource_id, $resource, 'sourceValue', $amount);

        $resource_id = $this->event->profile_id;
        $resource = Wise::getProfile($resource_id);
        $fullname = $resource->fullName ?? '';
        $this->addResource($wise_event_id, WiseResource::TYPE_PROFILE, $resource_id, $resource, 'fullName', $fullname);

        $resource_id = $this->event->account_id;
        $resource = Wise::getAccount($resource_id);
        $email = $resource->email ?? '';
        $this->addResource($wise_event_id, WiseResource::TYPE_ACCOUNT, $resource_id, $resource, 'email', $email);


        $this->event->status = WiseEvent::STATUS_PROCESSED;
        $this->event->save();

        if ($amount > 0 && !empty($email)) {
            $withdrawal = Withdrawal::inProgress()->whereAmount($amount)->whereEmail($email)->first();
            if (!empty($withdrawal)) {
                $withdrawal->status = Withdrawal::STATUS_DONE;
                $withdrawal->wise_event_id = $wise_event_id;
                $withdrawal->save();
            }
        }
    }

    protected function addResource($wise_event_id, $type, $resource_id, $resource, $key, $value)
    {
        WiseResource::create(compact('wise_event_id', 'type','resource_id', 'resource', 'key', 'value'));
    }
}
