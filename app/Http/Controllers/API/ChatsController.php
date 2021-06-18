<?php

namespace App\Http\Controllers\API;

use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatsController extends Controller
{
    /**
     * Добавить чат.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|TryException
     */
    public function addChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rate_id'   => 'required|integer|exists:rate,rate_id',
            'order_id'  => 'required|integer|exists:orders,order_id',
            'to_user'   => 'required|integer|exists:users,user_id',
            'user_id'   => 'required|integer|exists:users,user_id',
        ]);

        $this->returnValidated($validator);

        $data = $request->post();

        /**
         * TODO: Application rules
        */

        try {
            Chat::create([
                'rate_id'     => $data['rate_id'],
                'order_id'    => $data['order_id'],
                'user_id'     => $data['user_id'],
                'to_user'     => $data['to_user'],
                'chat_date'   => Carbon::now(),
                'chat_status' => 'active',
                'last_sms'    => '',
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
     * Получить список чатов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     */
    public function showChats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'user_id'   => 'required|integer|exists:users,user_id',
                'addressee' => 'integer|exists:users,user_id',
                'order_id'  => 'integer|exists:orders,order_id',
            ]
        );

        $this->returnValidated($validator);

        $data = $request->post();

        $query = Chat::query()->where('user_id', $data['user_id']);

        if(isset($data['addressee'])) {
            $query->where('to_user', $data['addressee']);
        }

        if(isset($data['order_id'])) {
            $query->where('order_id', $data['order_id']);
        }

        $chats = $query->get()->toArray();

        return response()->json([
            'status' => true,
            'number' => count($chats),
            'result' => null_to_blank($chats),
        ]);
    }

    /**
     * Удалить чат
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     */
    public function deleteChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'chat_id'  => 'integer|exists:chats,chat_id',
                'user_id'   => 'required|integer|exists:users,user_id',
            ]
        );

        $this->returnValidated($validator);

        $data = $request->post();

        $affected = DB::table('chats')
            ->where('chat_id', $data['chat_id'])
            ->where('user_id', $data['user_id'])
            ->delete();

        return response()->json(['status' => (bool)$affected]);
    }
}
