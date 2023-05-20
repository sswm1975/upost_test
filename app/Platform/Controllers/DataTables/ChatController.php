<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Chat;
use Encore\Admin\Layout\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends BaseController
{
    protected string $title = 'Чаты';
    protected string $icon = 'fa-shopping-bag';
    protected string $entity = 'chats';
    protected int $count_columns = 13;

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $menu_statuses = [Chat::STATUS_ACTIVE, Chat::STATUS_CLOSED];

        $statuses = [];
        foreach ($menu_statuses as $status) {
            $statuses[$status] = __("message.chat.statuses.$status");
        }
        $statuses['all'] =  'Все';

        return compact('statuses');
    }

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content): Content
    {
        $content = parent::index($content);

        $data = [
            'chat_lock_status' => Chat::LOCK_STATUSES,
        ];
        $content->row(view('platform.datatables.chats.modals', compact('data')));

        return $content;
    }

    /**
     * Получить данные для таблицы.
     *
     * @return array
     */
    public function getData()
    {
        $status = request('status', Chat::STATUS_ACTIVE);

        $data = Chat::with(['customer', 'performer', 'order', 'route', 'dispute.admin_user'])
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'status' => $row->status_name,
                    'lock_status' => Chat::LOCK_STATUSES[$row->lock_status],
                    'created_at' => $row->created_at->format('d.m.Y'),
                    'customer_id' => $row->customer->id,
                    'customer_full_name' => $row->customer->full_name,
                    'performer_id' => $row->performer->id,
                    'performer_full_name' => $row->performer->full_name,
                    'order_id' => $row->order->id,
                    'order_status' => $row->order->status_name,
                    'route_id' => $row->route->id,
                    'route_status' => $row->route->status_name,
                    'dispute_admin_user_name' => $row->dispute->admin_user->name ?? '',
                ];
            })
            ->all();

        return compact('data');
    }

    /**
     * Установка статуса блокировки для чата.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setChatLockStatus(Request $request): JsonResponse
    {
        if (!$request->filled(['ids', 'chat_lock_status'])) {
            return static::jsonResponse('Не заполнены обязательные параметры!', false);
        }

        $ids = json_decode($request->input('ids'));
        Chat::whereKey($ids)->update(['lock_status' => $request->input('chat_lock_status')]);

        return static::jsonResponse('Статус для блокировки чата назначен!');
    }

    /**
     * Сформировать JSON-ответ
     *
     * @param string $message
     * @param bool $status
     * @return JsonResponse
     */
    private static function jsonResponse(string $message, bool $status = true): JsonResponse
    {
        return response()->json(
            compact('status', 'message'),
            200,
            ['Content-Type' => 'application/json; charset=utf-8'],
            JSON_UNESCAPED_UNICODE
        );
    }
}
