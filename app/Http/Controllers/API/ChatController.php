<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Rate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
            'filter'  => 'nullable|in:all,customer,performer',
            'search'  => 'nullable|string|censor',
            'count'   => 'integer',
            'page'    => 'integer',
            'sorting' => 'in:asc,desc',
        ]);

        /**
         * @var string $filter       Фильтрация, кем выступает пользователь - Заказчиком или Исполнителем
         * @var string|null $search  Строка поиска
         * @var int|null $count      Кол-во чатов на странице
         * @var int|null $page       Номер страницы
         * @var string|null $sorting Сортировка
         * @var int $user_id         Код аутентифицированного пользователя
         */
        extract($data);
        $filter = $filter ?? 'all';
        $search = $search ?? '';
        $count = $count ?? self::DEFAULT_PER_PAGE;
        $page = $page ?? 1;

        # к запросу добавляяем поля:
        # authuser_unread_count - кол-во непрочитанных сообщений аутентифицированного пользователем
        # max_message_id - последнее сообщение по чату (для сортировки)
        $select = "
            chats.*,
            IF(performer_id = {$user_id}, performer_unread_count, 0) + IF(customer_id = {$user_id}, customer_unread_count, 0) AS authuser_unread_count,
            (SELECT MAX(id) FROM messages m WHERE m.chat_id = chats.id) AS max_message_id
        ";

        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $rows */
        $rows = Chat::interlocutors()
            ->addSelect(DB::raw($select))
            ->when($filter == 'customer', function ($query) use ($user_id) {
                return $query->where('customer_id', $user_id);
            })
            ->when($filter == 'performer', function ($query) use ($user_id) {
                return $query->where('performer_id', $user_id);
            })
            ->when(empty($search), function ($query) {
                return $query->orderBy('max_message_id', $sorting ?? self::DEFAULT_SORTING);
                # по таске https://app.asana.com/0/1202451331926444/1208755359488573/f захотели сортировать по дате последнего сообщения
                # соответственно сортировку ниже отключаем
//                return $query
//                    ->orderBy('authuser_unread_count', $sorting ?? self::DEFAULT_SORTING)
//                    ->orderBy('chats.id', $sorting ?? self::DEFAULT_SORTING);
            })
            ->when(!empty($search), function ($query) use ($search, $user_id) {
                return $query->bySearch($search, $user_id);
            })
           ->paginate($count, ['*'], 'page', $page);

        # подгружаем связи
        $rows->load([
            'interlocutor:id,name,surname,photo,scores_count,reviews_count',
            'order:id,name,price,currency,price_usd,user_price_usd,images,status',
            'last_message',
            'last_message.user:id,name,surname',
        ]);

        # узнаем, доставлен ли заказ
        $rows->loadCount([
            'rate AS is_delivered' => function ($query) {
                $query->delivered();
            }
        ]);

        # убираем лишнее
        $rows->each(function ($chat) {
            if (isset($chat->last_message->user->full_name)) {
                $chat->interlocutor->makeHidden('status_name', 'gender_name', 'validation_name', 'register_date_human', 'last_active_human', 'age');
                $chat->last_message->short_text = Str::limit($chat->last_message->text, self::LAST_MESSAGE_TEXT_LIMIT);
                $chat->last_message->user_full_name = $chat->last_message->user->full_name;
                $chat->last_message->short_name = $chat->last_message->user->short_name;
                $chat->last_message->makeHidden('user');
            }
            unset($chat->max_message_id);
        });

        # формируем ответ
        return response()->json([
            'status' => true,
            'count'  => $rows->total(),
            'page'   => $rows->currentPage(),
            'pages'  => $rows->lastPage(),
            'unread_messages' => $rows->sum('authuser_unread_count'),
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
            'message'       => __('message.chat_closed'),
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

        if (! $chat = Chat::find($chat_id)) throw new ErrorException(__('message.chat_not_found'));

        # авторизированный пользователь должен быть владельцем заказа или маршрута
        if (! in_array($request->user()->id, [$chat->performer_id, $chat->customer_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        # связываем чат со ставкой
        if (empty($chat->rate)) {
            Rate::query()
                ->where([
                    'route_id' => $chat->route_id,
                    'order_id' => $chat->order_id
                ])
                ->update(['chat_id' => $chat->id]);
        };

        return MessagesController::getMessages($chat, $data);
    }

    /**
     * Получить количество непрочитанных сообщений.
     *
     * @return JsonResponse
     */
    public function getCountUnreadMessages(): JsonResponse
    {
        $user_id = request()->user()->id;

        return response()->json([
            'status' => true,
            'count'  => Chat::getCountUnreadMessages($user_id),
        ]);
    }

    /**
     * Обнулити кількість непрочитаних повідомлень по вибраному чату.
     *
     * @param int $chat_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function clearUnreadCount(int $chat_id, Request $request): JsonResponse
    {
        $user_id = $request->user()->id;

        if (! $chat = Chat::find($chat_id)) {
            throw new ErrorException(__('message.chat_not_found'));
        }

        # авторизированный пользователь должен быть владельцем заказа или маршрута
        if (! in_array($user_id, [$chat->performer_id, $chat->customer_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        if ($user_id == $chat->performer_id) {
            $chat->performer_unread_count = 0;
        } else {
            $chat->customer_unread_count = 0;
        }
        $chat->save();

        return response()->json(['status' => true]);
    }
}
