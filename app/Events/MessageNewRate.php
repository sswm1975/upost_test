<?php

namespace App\Events;

use App\Models\Rate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageNewRate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private int $user_id;
    public Rate $rate;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Rate $rate)
    {
        # владельцу заказа будет отправлено сообщение
        $this->user_id = $rate->order->user_id;

        # сообщение будет состоять из данных ставки
        $this->rate = $rate;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->user_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'messages.new_rate';
    }
}
