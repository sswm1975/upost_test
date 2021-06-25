<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Queue\SerializesModels;

class OrderBanned
{
    use SerializesModels;

    public Order $order;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
