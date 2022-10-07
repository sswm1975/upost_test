<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NoticeController extends Controller
{
    /**
     * Получить уведомления.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $lang = $request->user()->lang;

        $notices = Notice::owner()
            ->join('notice_types', 'notice_types.id', 'notices.notice_type')
            ->when($request->get('status', 'all') == 'not_read', function ($query) {
                $query->where('notices.is_read', 0);
            })
            ->when($request->get('status', 'all') == 'read', function ($query) {
                $query->where('notices.is_read', 1);
            })
            ->latest('id')
            ->get([
                'notices.id',
                'notices.notice_type AS type',
                "notice_types.name_{$lang} as message",
                'notices.is_read',
                'notices.object_id',
                'notices.data',
                'notices.created_at',
            ]);

        return response()->json([
            'status'  => true,
            'notices' => null_to_blank($notices),
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
            'affected_rows' => $affected_rows,
        ]);
    }
}
