<?php

namespace App\Events;

use App\Models\Action;
use Illuminate\Broadcasting\Channel;
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
     * Признак транслировать событие через публичный канал.
     *
     * @var bool
     */
    private bool $is_public;

    /**
     * Создать новый экземпляр события.
     *
     * @return void
     */
    public function __construct(Action $action, bool $is_public)
    {
        $this->action = $action;
        $this->is_public = $is_public;
    }

    /**
     * Получить каналы трансляции события.
     *
     * @return PrivateChannel|Channel
     */
    public function broadcastOn()
    {
        if ($this->is_public) {
            return new Channel('public');
        }
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
