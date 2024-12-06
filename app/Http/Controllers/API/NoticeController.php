<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NoticeController extends Controller
{
    const DEFAULT_SORTING = 'desc';

    /**
     * Получить уведомления.
     *
     * @param Request $request
     * @throws ValidatorException|ValidationException
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'status'        => 'nullable|in:all,not_read,read',
            'sorting'       => 'in:asc,desc',
            'inner_sorting' => 'in:asc,desc',
        ]);
        $data['status'] = $data['status'] ?? 'all';

        $lang = $request->user()->lang;

        $notices = Notice::query()
            ->select(
                'notices.id',
                'notices.notice_type AS type',
                DB::Raw("
                    CASE
                      WHEN notices.notice_type = 'service_notice'
                      THEN CONCAT(notice_types.text_{$lang}, ': ', JSON_UNQUOTE(JSON_EXTRACT(notices.data, '$.text')))
	                  ELSE notice_types.text_{$lang}
                    END AS message
                "),
                'notices.is_read',
                'notices.object_id',
                'notices.data',
                'notices.created_at',
                'notices.updated_at',
                DB::raw('DATE(notices.created_at) AS arcdate'))
            ->join('notice_types', 'notice_types.id', 'notices.notice_type')
            ->owner()
            ->when($data['status'] == 'not_read', function ($query) {
                $query->where('notices.is_read', 0);
            })
            ->when($data['status'] == 'read', function ($query) {
                $query->where('notices.is_read', 1);
            })
            ->orderBy('arcdate', $data['sorting'] ?? self::DEFAULT_SORTING)
            ->orderBy('id', $data['inner_sorting'] ?? self::DEFAULT_SORTING)
            ->get()
            ->groupBy('arcdate')
            ->makeHidden('arcdate')
            ->all();

        # узнаем общее кол-во непрочитанных сообщений текущем пользователем по всем чатам и как заказчик и как исполнитель.
        $user_id = request()->user()->id;
        $unread_messages = (int) DB::selectOne("
          SELECT SUM(IF(performer_id = {$user_id}, performer_unread_count, 0) + IF(customer_id = {$user_id}, customer_unread_count, 0)) AS unread_messages
          FROM chats
          WHERE performer_id = {$user_id} OR customer_id = {$user_id}
        ")->unread_messages ?? 0;

        return response()->json([
            'status'  => true,
            'notices' => null_to_blank($notices),
            'unread_messages' => $unread_messages,
        ]);
    }

    /**
     * Установить признак прочтения уведомления.
     *
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function setReadNotice(): JsonResponse
    {
        $data = validateOrExit([
            'id'   => 'required|array|min:1',
            'id.*' => 'required|integer',
        ]);

        $affected_rows = Notice::owner()
            ->whereKey($data['id'])
            ->where('is_read', '=', 0)
            ->update(['is_read' => 1]);

        return response()->json([
            'status'        => true,
            'message' => __('message.notification_read'),
            'affected_rows' => $affected_rows,
        ]);
    }
}
