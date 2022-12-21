<?php

namespace App\Events;

use App\Models\Notice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoticeEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Экземпляр уведомления.
     *
     * @var Notice
     */
    public Notice $notice;

    /**
     * Создать новый экземпляр события.
     *
     * @return void
     */
    public function __construct(Notice $notice)
    {
        $this->notice = $notice;
    }

    /**
     * Получите данные для трансляции.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $notice = $this->notice;

        return [
            'id'                 => $notice->id,
            'type'               => $notice->notice_type,
            'object_id'          => $notice->object_id,
            'text'               => config("notice_types.{$notice->notice_type}.text_{$notice->user->lang}"),
            'data'               => $notice->data ?? [],
            'created_at'         => $notice->created_at->format('Y-m-d H:i:s'),
            'created_at_format1' => $notice->created_at_format1,
            'created_at_format2' => $notice->created_at_format2,
            'created_at_format3' => $notice->created_at_format3,
        ];
    }

    /**
     * Получить каналы трансляции события.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->notice->user_id);
    }

    /**
     * Получить имя транслируемого события.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'notices';
    }
}
