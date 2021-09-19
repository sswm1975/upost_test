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

class ChatController extends Controller
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
            'rate_id'   => 'required|integer|exists:rate,id',
            'order_id'  => 'required|integer|exists:orders,id',
            'to_user'   => 'required|integer|exists:users,id',
            'user_id'   => 'required|integer|exists:users,id',
        ]);

        $data = validateOrExit($validator);

        try {
            $this->addExtValidate($data);

            Chat::create([
                'rate_id'     => $data['rate_id'],
                'order_id'    => $data['order_id'],
                'user_id'     => $data['user_id'],
                'to_user'     => $data['to_user'],
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
        $data = validateOrExit([
            'user_id'   => 'required|integer|exists:users,id',
            'addressee' => 'integer|exists:users,id',
            'order_id'  => 'integer|exists:orders,id',
        ]);

        try {
            $query = Chat::query()
                ->where('user_id', $data['user_id'])
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
                if($chat['status'] == Chat::STATUS_ACTIVE) {
                    $res[] = $chat;
                } elseif($chat['user_id'] == $data['user_id']) {
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
        $data = validateOrExit([
            'chat_id' => 'required|integer|exists:chats,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $this->deleteExtValidate($data);

            $affected = DB::table('chats')
                ->where('id', $data['chat_id'])
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
        $count = Rate::whereId($data['rate_id'])->whereOrderId($data['order_id'])->count();

        if ($count == 0) {
            throw new Exception("This rate belongs to other order");
        }

        // Check user_id
        $count = Rate()->query()
            ->where('id', $data['rate_id'])
            ->where('who_start', $data['user_id'])
            ->where('user_id', $data['to_user'])
            ->count();

        if ($count == 0) {
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
        $count = $query->where('id', $data['chat_id'])
            ->where('status', '<>', Chat::STATUS_ACTIVE)
            ->count();

        if ($count == 0) {
            throw new Exception("This chat now is active. Can not delete chat.");
        }

        $count = Chat::whereId($data['chat_id'])->whereUserId($data['user_id'])->count();

        if ($count == 0) {
            throw new Exception("This chat created by other user. Can not delete chat.");
        }

        return true;
    }
}
