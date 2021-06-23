<?php

namespace App\Http\Controllers\API;

use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Rate;
use Carbon\Carbon;
use Exception;
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

        validateOrExit($validator);

        $data = $request->all();

        try {
            $this->addExtValidate($data);

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
        catch (Exception $e) {
            throw new TryException($e->getMessage());
        }
    }

    /**
     * Получить список чатов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     * @throws TryException
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

        validateOrExit($validator);

        $data = $request->post();

        try {

            $query = Chat::query()->where('user_id', $data['user_id'])
                ->orWhere('to_user', $data['user_id']);

            if(isset($data['addressee'])) {
                $query = $query->where('to_user', $data['addressee']);
            }

            if(isset($data['order_id'])) {
                $query = $query->where('order_id', $data['order_id']);
            }

            $chats = $query->get()->toArray();

            $res = [];
            foreach ($chats as $chat) {
                if($chat['chat_status'] == "active") {
                    $res[] = $chat;
                } elseif($chat["user_id"] == $data["user_id"]) {
                    $res[] = $chat;
                }
            }

            return response()->json([
                'status' => true,
                'number' => count($res),
                'result' => null_to_blank($res),
            ]);
        }
        catch (Exception $e) {
            throw new TryException($e->getMessage());
        }
    }

    /**
     * Удалить чат
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     * @throws TryException
     */
    public function deleteChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'chat_id'  => 'required|integer|exists:chats,chat_id',
                'user_id'   => 'required|integer|exists:users,user_id',
            ]
        );

        validateOrExit($validator);

        $data = $request->all();

        try {

            $this->deleteExtValidate($data);

            $affected = DB::table('chats')
                ->where('chat_id', $data['chat_id'])
                ->where('user_id', $data['user_id'])
                ->delete();
        }
        catch (Exception $e) {
            throw new TryException($e->getMessage());
        }

        return response()->json(['status' => (bool)$affected]);
    }

    /**
     * Validation request data for add chat
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    private function addExtValidate(array $data): bool
    {
        // Check rate_id
        $query = (new Rate())->newQuery();
        $count = $query->where('rate_id', $data['rate_id'])
            ->where('order_id', $data['order_id'])
            ->count();

        if($count == 0) {
            throw new Exception("This rate belongs to other order");
        }

        // Check user_id
        $query = (new Rate())->newQuery();
        $count = $query->where('rate_id', $data['rate_id'])
            ->where('who_start', $data['user_id'])
            ->where('user_id', $data['to_user'])
            ->count();

        if($count == 0) {
            throw new Exception("This User not have permissions for rate");
        }

        return true;
    }

    /**
     * Validation request data for delete chat
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    private function deleteExtValidate(array $data): bool
    {
        $query = (new Chat())->newQuery();
        $count = $query->where('chat_id', $data['chat_id'])
            ->where('chat_status', '<>', 'active')
            ->count();

        if($count == 0) {
            throw new Exception("This chat now is active. Can not delete chat.");
        }

        $query = (new Chat())->newQuery();
        $count = $query->where('chat_id', $data['chat_id'])
            ->where('user_id', $data['user_id'])
            ->count();

        if($count == 0) {
            throw new Exception("This chat created by other user. Can not delete chat.");
        }

        return true;

    }
}
