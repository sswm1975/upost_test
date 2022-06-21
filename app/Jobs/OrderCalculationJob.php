<?php

namespace App\Jobs;

use App\Models\Order;
use App\Modules\Calculations;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderCalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Order $order;
    private bool $recalculation;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order, bool $recalculation = false)
    {
        $this->order = $order;
        $this->recalculation = $recalculation;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        Calculations::run($this->order, $this->recalculation);
    }
}
