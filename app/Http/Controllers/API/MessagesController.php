<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Order;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
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
            'chat_id' => 'required|integer',
            'text'    => 'required|string|censor',
            'files'   => 'nullable|array|max:8',
            'files.*' => 'nullable|string',
        ]);

        $auth_user_id = $request->user()->id;

        $chat = Chat::find($data['chat_id']);
        if (! in_array($auth_user_id, [$chat->performer_id, $chat->customer_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        Message::create($data);

        $chat->increment($auth_user_id == $chat->performer_id ? 'customer_unread_count' : 'performer_unread_count');

        return response()->json(['status' => true]);
    }

    /**
     * Получить список сообщений.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function showMessages(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'route_id' => 'required|integer',
            'order_id' => 'required|integer',
            'count'    => 'integer',
            'page'     => 'integer',
            'sorting'  => 'in:asc,desc',
        ]);

        /**
         * @var int $route_id
         * @var int $order_id
         * @var int|null $count
         * @var int|null $page
         * @var string|null $sorting
         */
        extract($data);

        $route = Route::find($route_id, ['user_id']);
        $order = Order::find($order_id, ['user_id']);

        # авторизированный пользователь должен быть владельцем заказа или маршрута
        if (empty($route) || empty($order) || !in_array($request->user()->id, [$route->user_id, $order->user_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        $chat = Chat::whereRouteId($route_id)->whereOrderId($order_id)->first();

        if (empty($chat)) {
            $chat = Chat::create([
                'route_id'     => $route_id,
                'order_id'     => $order_id,
                'performer_id' => $route->user_id,
                'customer_id'  => $order->user_id,
            ]);
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

        # получаем сообщения по чату
        $rows = Message::whereChatId($chat->id)
            ->orderBy('id', $sorting ?? self::DEFAULT_SORTING)
            ->paginate($count ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $page ?? 1)
            ->toArray();

        return response()->json([
            'status'   => true,
            'count'    => $rows['total'],
            'page'     => $rows['current_page'],
            'pages'    => $rows['last_page'],
            'chat'     => null_to_blank($chat),
            'messages' => null_to_blank($rows['data']),
        ]);
    }
}
