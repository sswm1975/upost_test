<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Dispute;
use App\Models\Message;
use App\Models\Order;
use App\Models\Rate;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessagesController extends Controller
{
    const DEFAULT_PER_PAGE = 5;
    const DEFAULT_SORTING = 'asc';

    /**
     * Добавить сообщение.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function addMessage(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'chat_id'  => 'required|integer',
            'text'     => 'required|string|censor',
            'images'   => 'nullable|array|max:8',
            'images.*' => 'nullable|string',
        ]);

        $auth_user_id = $request->user()->id;

        $chat = Chat::find($data['chat_id']);
        if (! in_array($auth_user_id, [$chat->performer_id, $chat->customer_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        # проверки на блокировку создания сообщения
        if (
            $chat->lock_status == Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL ||
            ($auth_user_id == $chat->customer_id && in_array($chat->lock_status, [Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ONLY_CUSTOMER, Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_PERFORMER])) ||
            ($auth_user_id == $chat->performer_id && in_array($chat->lock_status, [Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ONLY_PERFORMER, Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_CUSTOMER]))
        ) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.lock_add_message')],
            ]);
        }

        Message::create($data);

        # меняем статусы блокировки
        $extra = [];
        if ($auth_user_id == $chat->customer_id) {
            # заказчику было разрешено одно сообщение - меняем на блокировку всем
            if ($chat->lock_status == Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_CUSTOMER) {
                $extra = ['lock_status' => Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL];
            }
            # всем было разрешено одно сообщение - меняем на разрешено исполнителю одно сообщение
            if ($chat->lock_status == Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ALL) {
                $extra = ['lock_status' => Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_PERFORMER];
            }
        }
        if ($auth_user_id == $chat->performer_id) {
            # исполнителю было разрешено одно сообщение - меняем на блокировку всем
            if ($chat->lock_status == Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_PERFORMER) {
                $extra = ['lock_status' => Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL];
            }
            # всем было разрешено одно сообщение - меняем на разрешено заказчику одно сообщение
            if ($chat->lock_status == Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ALL) {
                $extra = ['lock_status' => Chat::LOCK_STATUS_PERMIT_ONE_MESSAGE_ONLY_CUSTOMER];
            }
        }

        $chat->increment($auth_user_id == $chat->performer_id ? 'customer_unread_count' : 'performer_unread_count', 1, $extra);

        $recipient_id = $auth_user_id == $chat->performer_id ? $chat->customer_id : $chat->performer_id;
        Chat::broadcastCountUnreadMessages($recipient_id);

        # если существует спор, то увеличиваем счетчик непрочитанных сообщений менеджером спора
        if (Dispute::existsForChat($chat->id)) {
            $chat->dispute->increment('unread_messages_count');
        }

        return response()->json([
            'status' => true,
            'lock_status' => $chat->lock_status,
        ]);
    }

    /**
     * Получить список сообщений по кодам маршрута и заказа.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function showMessages(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'route_id'         => 'required|integer',
            'order_id'         => 'required|integer',
            'is_group_by_date' => 'boolean',
            'count'            => 'integer',
            'page'             => 'integer',
            'sorting'          => 'in:asc,desc',
        ]);

        /**
         * @var int $route_id
         * @var int $order_id
         */
        extract($data);

        $route = Route::find($route_id, ['user_id']);
        $order = Order::find($order_id, ['user_id']);

        # авторизированный пользователь должен быть владельцем заказа или маршрута
        if (empty($route) || empty($order) || !in_array($request->user()->id, [$route->user_id, $order->user_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        # ищем существующий чат или создаем новый
        $chat = Chat::searchOrCreate($route_id, $order_id, $route->user_id, $order->user_id);

        # связываем чат со ставкой
        if (empty($chat->rate)) {
            Rate::whereRouteId($route_id)->whereOrderId($order_id)->update(['chat_id' => $chat->id]);
        };

        return static::getMessages($chat, $data);
    }

    /**
     * Получить сообщения по чату.
     *
     * @param Chat $chat
     * @param array $data
     * @return JsonResponse
     */
    public static function getMessages(Chat &$chat, array &$data): JsonResponse
    {
        # дополняем Чат данными об Исполнителе, Заказчике, Маршруте, Заказе и Ставке
        $chat->load([
            'performer:id,name,surname,photo,birthday,gender,status,validation,register_date,last_active,scores_count,reviews_count',
            'customer:id,name,surname,photo,birthday,gender,status,validation,register_date,last_active,scores_count,reviews_count',
            'route',
            'route.from_country',
            'route.from_city',
            'route.to_country',
            'route.to_city',
            'order',
            'rate',
            'dispute',
            'dispute.problem',
        ]);

        # обнуляем счетчик "Кол-во непрочитанных сообщений по чату"
        $field = request()->user()->id == $chat->performer_id ? 'performer_unread_count' : 'customer_unread_count';
        if ($chat->$field) {
            DB::table('chats')->where('id', $chat->id)->update([$field => 0]);
        }

        # если чат просматривает владелец ставки, то устанавливаем "Да" для "Подтвержденная ставка просмотрена исполнителем?"
        if (!empty($chat->rate) && $chat->rate->user_id == request()->user()->id) {
            $chat->rate->viewed_by_performer = true;
            $chat->rate->save();
        }

        # получаем сообщения по чату сгруппированные по дате создания
        if ($data['is_group_by_date'] ?? true) {
            $messages = Message::whereChatId($chat->id)
                ->selectRaw('*, DATE(created_at) AS created_date')
                ->orderBy('id', $data['sorting'] ?? self::DEFAULT_SORTING)
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
            ->orderBy('id', $data['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($data['count'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $data['page'] ?? 1)
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
