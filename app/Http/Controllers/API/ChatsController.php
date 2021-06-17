<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChatsController extends Controller
{
    /**
     * Добавить чат.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|TryException
     * @throws ErrorException
     */
    public function addChat(Request $request): JsonResponse
    {
        $userId     = $request->post('user_id');
        $rateId     = $request->post('rate_id');
        $orderId    = $request->post('order_id');

        if(is_null($userId)) {
            throw new ErrorException('Invalid user_id field in request');
        }

        if(is_null($rateId)) {
            throw new ErrorException('Invalid rate_id field in request');
        }

        if(is_null($orderId)) {
            throw new ErrorException('Invalid order_id field in request');
        }

        $validator = Validator::make($request->all(),
            [
                'rate_id' => [
                    'required',
                    'integer',
                    'exists:rate,rate_id',
                    Rule::unique('rate', 'rate_id')->where(function($query) use ($userId, $rateId) {
                        return $query->where('rate_id', $rateId)->where('user_id', $userId);
                    }),
                ],
                'order_id'  => [
                    'required',
                    'integer',
                    'exists:orders,order_id',
                    Rule::unique('rate', 'rate_id')->where(function($query) use ($userId, $rateId, $orderId) {
                        return $query->where('rate_id', $rateId)->where('user_id', $userId)->where('orderId', $orderId);
                    }),
                ],
                'to_user'  => [
                    'required',
                    'integer',
                    'exists:users,user_id'
                ],
                'user_id'  => [
                    'required',
                    'integer',
                    'exists:users,user_id'
                ],
            ],
        );
        $this->returnValidated($validator);

        $data = $request->post();

        try {
            Chat::create([
                'rate_id'  => $data['rate_id'],
                'order_id'  => $data['order_id'],
                'user_id'  => $data['user_id'],
                'to_user'  => $data['to_user'],
                'chat_date'   => Carbon::now(),
                'chat_status' => 'active',
                'last_sms' => '',
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
}
