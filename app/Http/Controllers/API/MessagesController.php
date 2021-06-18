<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Message;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MessagesController extends Controller
{
    /**
     * Добавить сообщение.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|TryException
     */
    public function addMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'chat_id'           => 'required|integer|exists:chats,chat_id',
                'rate_id'           => 'required|integer|exists:rate,rate_id',
                'order_id'          => 'required|integer|exists:orders,order_id',
                'from_user'         => 'required|integer|exists:users,user_id',
                'to_user'           => 'required|integer|exists:users,user_id',
                'message_attach'    => 'required|string',
                'type'              => 'string'
            ]
        );
        $this->returnValidated($validator);

        $data = $request->post();

        /*
         * TODO: Add Ext Validation
         * */

        $data['type'] = $data['type'] ?? 'simple';

        try {
            Message::create([
                'chat_id'           => $data['chat_id'],
                'rate_id'           => $data['rate_id'],
                'order_id'          => $data['order_id'],
                'from_user'         => $data['from_user'],
                'to_user'           => $data['to_user'],
                'message_date'      => $data['message_date'],
                'message_text'      => $data['message_text'],
                'message_attach'    => $data['message_attach'],
                'type'              => $data['type'],
            ]);

            return response()->json([
                'status' => true,
            ]);
        }
        catch (\Exception $e) {
            throw new TryException($e->getMessage());
        }
    }

    /**
     * Получить список сообщений.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     */
    public function showMessages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required|integer|exists:users,user_id',
                'chat_id' => 'required|integer|exists:chats,chat_id',
                'count'   => 'integer',
                'page'    => 'integer',
                'sorting' => 'integer',
            ]
        );

        $this->returnValidated($validator);

        $data = $request->all();

        $data['sorting'] = $data['sorting'] ?? 'ASC';

        $query = Message::query()
            ->where('from_id', $data['user_id'])
            ->where('chat_id', $data['chat_id']);

        if(isset($data['page']) && isset($data['count'])) {
            $offset = (int)$data['page'] * (int)$data['count'];
            $query->offset($offset);
            $query->limit($data['count']);
        }

        $messages = $query->orderBy('message_date', $data['sorting'])->get()->toArray();

        return response()->json([
            'status' => true,
            'number' => count($messages),
            'result' => null_to_blank($messages),
        ]);
    }
}
