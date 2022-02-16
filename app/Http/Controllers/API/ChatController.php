<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\ValidatorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    const DEFAULT_PER_PAGE = 5;
    const DEFAULT_SORTING = 'desc';
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
            'search'  => 'nullable|string|censor',
            'count'   => 'integer',
            'page'    => 'integer',
            'sorting' => 'in:asc,desc',
        ]);

        /**
         * @var string $filter
         * @var string|null $search
         * @var int|null $count
         * @var int|null $page
         * @var string|null $sorting
         */
        extract($data);
        $filter = $filter ?? 'all';
        $search = $search ?? '';

        $rows = Chat::interlocutors()
            ->with([
                'interlocutor:id,name,photo,scores_count,reviews_count',
                'order:id,name,price,currency,price_usd,user_price,user_currency,user_price_usd,images,status',
                'last_message',
                'last_message.user:id,name',
            ])
            ->withCount(['rate as is_delivered' => function ($query) {
                $query->delivered();
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
            ->when(!empty($search), function ($query) use ($search) {
                return $query->searchMessage($search);
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

    /**
     * Получить список сообщений по коду чата.
     *
     * @param int $chat_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function showMessages(int $chat_id, Request $request): JsonResponse
    {
        $data = validateOrExit([
            'is_group_by_date' => 'boolean',
            'count'            => 'integer',
            'page'             => 'integer',
            'sorting'          => 'in:asc,desc',
        ]);

        /**
         * @var bool $is_group_by_date
         * @var int|null $count
         * @var int|null $page
         * @var string|null $sorting
         */
        extract($data);

        $chat = Chat::find($chat_id);

        if (! $chat) throw new ErrorException(__('message.chat_not_found'));

        $auth_user_id = $request->user()->id;

        # авторизированный пользователь должен быть владельцем заказа или маршрута
        if (! in_array($auth_user_id, [$chat->performer_id, $chat->customer_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        # дополняем Чат данными об Исполнителе, Заказчике, Маршруте и Заказе
        $chat->load([
            'performer:id,name,photo,birthday,gender,status,validation,register_date,last_active,scores_count,reviews_count',
            'customer:id,name,photo,birthday,gender,status,validation,register_date,last_active,scores_count,reviews_count',
            'route',
            'route.from_country',
            'route.from_city',
            'route.to_country',
            'route.to_city',
            'order:id,name,price,currency,price_usd,user_price,user_currency,user_price_usd,images,status',
        ]);

        # обнуляем счетчик "Кол-во непрочитанных сообщений по чату"
        $field = $auth_user_id == $chat->performer_id ? 'customer_unread_count' : 'performer_unread_count';
        if ($chat->$field) {
            DB::table('chats')->where('id', $chat->id)->update([$field => 0]);
        }

        # получаем сообщения по чату сгруппированные по дате создания
        if ($is_group_by_date ?? true) {
            $messages = Message::whereChatId($chat->id)
                ->selectRaw('*, DATE(created_at) AS created_date')
                ->orderBy('id', $sorting ?? self::DEFAULT_SORTING)
                ->get()
                ->groupBy('created_date')
                ->makeHidden('created_date')
                ->all();

            return response()->json([
                'status'   => true,
                'chat'     => null_to_blank($chat),
                'messages' => null_to_blank($messages),
            ]);
        }

        # получаем сообщения по чату с пагинацией
        $messages = Message::whereChatId($chat->id)
            ->orderBy('id', $sorting ?? self::DEFAULT_SORTING)
            ->paginate($count ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $page ?? 1)
            ->toArray();

        return response()->json([
            'status'   => true,
            'count'    => $messages['total'],
            'page'     => $messages['current_page'],
            'pages'    => $messages['last_page'],
            'chat'     => null_to_blank($chat),
            'messages' => null_to_blank($messages['data']),
        ]);
    }
}
