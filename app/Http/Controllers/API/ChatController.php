<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Rate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\ValidatorException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    const DEFAULT_PER_PAGE = 5;
    const DEFAULT_SORTING = 'asc';
    const LAST_MESSAGE_TEXT_LIMIT = 50;

    /**
     * Получить список чатов для авторизированного пользователя.
     *
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function showChats(): JsonResponse
    {
        $data = validateOrExit([
            'filter'  => 'nullable|in:all,waiting,delivered,closed',
            'count'   => 'integer',
            'page'    => 'integer',
            'sorting' => 'in:asc,desc',
        ]);

        /**
         * @var string $filter
         * @var int|null $count
         * @var int|null $page
         * @var string|null $sorting
         */
        extract($data);
        $filter = $filter ?? 'all';

        $rows = Chat::interlocutors()
            ->with([
                'interlocutor:id,name,photo,scores_count,reviews_count',
                'order:id,name,price,currency,price_usd,user_price,user_currency,user_price_usd,images,status',
                'last_message',
                'last_message.user:id,name',
            ])
            ->withCount(['rate as is_delivered' => function ($query) {
                $query->whereIn('status', Rate::STATUSES_DELIVERED);
            }])
            ->when($filter == 'waiting', function ($query) {
                return $query->waiting();
            })
            ->when($filter == 'delivered', function ($query) {
                return $query->delivered();
            })
            ->when($filter == 'closed', function ($query) {
                return $query->closed();
            })
            ->orderBy('chats.id', $sorting ?? self::DEFAULT_SORTING)
            ->paginate($count ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $page ?? 1);

        # убираем лишнее
        $rows->each(function ($chat) {
            if (isset($chat->last_message->user->full_name)) {
                $chat->interlocutor->makeHidden('status_name', 'gender_name', 'validation_name', 'register_date_human', 'last_active_human', 'age');
                $chat->last_message->short_text = Str::limit($chat->last_message->text, self::LAST_MESSAGE_TEXT_LIMIT);
                $chat->last_message->user_full_name = $chat->last_message->user->full_name;
                $chat->last_message->makeHidden('user');
            }
        });

        return response()->json([
            'status' => true,
            'count'  => $rows->total(),
            'page'   => $rows->currentPage(),
            'pages'  => $rows->lastPage(),
            'chats'  => null_to_blank($rows->toArray()['data']),
            'sql'=>getSQLForFixDatabase()
        ]);
    }

    /**
     * Закрыть чат (только для админа и модератора).
     *
     * @param int $chat_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function closeChat(int $chat_id, Request $request): JsonResponse
    {
        if (! in_array($request->user()->role, [User::ROLE_ADMIN, User::ROLE_MODERATOR])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        $affected_rows = Chat::whereKey($chat_id)->update(['status' => Chat::STATUS_CLOSED]);

        return response()->json([
            'status'        => $affected_rows > 0,
            'affected_rows' => $affected_rows,
        ]);
    }
}
