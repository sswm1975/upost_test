<?php

namespace App\Jobs;

use App\Models\Rate;
use App\Models\Job;
use App\Models\Chat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CloseDeadlineRate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Количество дней до истечения срока. */
    const COUNT_DAYS_EXPIRE = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rates = $this->getRatesDeadlineTermExpired();
        foreach ($rates as $rate) {
            $rate->update(['rate_status' => Rate::STATUS_SUCCESSFUL]);
            Job::where('rate_id', $rate->rate_id)->update(['job_status' => Job::STATUS_SUCCESSFUL]);
            Chat::where('rate_id', $rate->rate_id)->update(['chat_status' => Chat::STATUS_SUCCESSFUL]);
        }
    }

    /**
     * Get expired rates.
     *
     * @return mixed
     */
    private function getRatesDeadlineTermExpired()
    {
        return Rate::deadlineTermExpired(self::COUNT_DAYS_EXPIRE)->get(['rate_id']);
    }
}
