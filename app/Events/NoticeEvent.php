<?php

namespace App\Events;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoticeEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private int $user_id;

    public int $id;
    public string $type;
    public int $object_id;
    public string $text;
    public array $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Notice $notice)
    {
        # этому пользователю отправляем сообщение
        $this->user_id = $notice->user_id;

        # определяем язык у пользователя
        $lang = User::whereKey($notice->user_id)->value('lang');

        # формируем уведомление
        $this->id        = $notice->id;
        $this->type      = $notice->notice_type;
        $this->object_id = $notice->object_id;
        $this->text      = config("notice_types.{$notice->notice_type}.text_{$lang}");
        $this->data      = $notice->data ?? [];
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
        return 'notices';
    }
}
