<?php

namespace App\Events;

use App\Models\Action;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Экземпляр действия.
     *
     * @var Action
     */
    public Action $action;

    /**
     * Создать новый экземпляр события.
     *
     * @return void
     */
    public function __construct(Action $action)
    {
        $this->action = $action;
    }

    /**
     * Получить каналы трансляции события.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->action->user_id);
    }

    /**
     * Получить имя транслируемого события.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'actions';
    }
}
