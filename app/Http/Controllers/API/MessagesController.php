<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $data = validateOrExit(
            [
                'chat_id'    => 'required|integer|exists:chats,id',
                'rate_id'    => 'required|integer|exists:rate,id',
                'order_id'   => 'required|integer|exists:orders,id',
                'from_user'  => 'required|integer|exists:users,id',
                'to_user'    => 'sometimes|required|integer|exists:users,id',
                'text'       => 'required|string',
                'files'      => 'required|array|max:8',
                'files.*'    => 'required|string',
                'type'       => 'sometimes|required|in:' . implode(',', Message::TYPES),
            ]
        );

        $user_id = $request->user()->id;
        $chat_user_id = Chat::find($data['chat_id'])->user_id;

        if ($chat_user_id != $user_id && $data['to_user'] != $user_id) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        Message::create($data);

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Получить список сообщений.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function showMessages(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'user_id' => 'required|integer|exists:users,id',
            'chat_id' => 'required|integer|exists:chats,id',
            'count'   => 'integer',
            'page'    => 'integer',
            'sorting' => 'integer',
        ]);

        $rows = Message::query()
            ->where('from_user', $data['user_id'])
            ->where('id', $data['chat_id'])
            ->orderBy('created_by', $data['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($data['count'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $data['page'] ?? 1)
            ->toArray();

        return response()->json([
            'status' => true,
            'count'  => $rows['total'],
            'page'   => $rows['current_page'],
            'pages'  => $rows['last_page'],
            'result' => null_to_blank($rows['data']),
        ]);
    }

    /**
     * Подтверждение совершения покупки (исполнитель).
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function acceptShoppingByPerformer(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'chat_id' => 'required|integer|exists:chats,id',
            'files'   => 'required|array|max:8',
            'files.*' => 'required|string',
        ]);

        $chat = Chat::with('rate')->find($request->chat_id);


        if (!$chat) throw new ErrorException(__('message.chat_not_found'));

        $user_id = $request->user()->id;

        # запрещено создавать задание, если пользователь к этой ставке не имеет отношения
        if ($chat->rate->who_start <> $user_id && $chat->rate->user_id <> $user_id) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        Message::create([
            'chat_id' => $data['chat_id'],
            'files'   => $data['files'],
            'type'    => Message::TYPE_PRODUCT_CONFIRMATION,
        ]);

        return response()->json([
            'status' => true,
        ]);
    }
}
