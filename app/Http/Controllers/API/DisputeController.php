<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\DisputeProblem;
use App\Models\Rate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use App\Exceptions\ValidatorException;
use App\Models\Dispute;

class DisputeController extends Controller
{
    /**
     * Добавить спор.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function addDispute(Request $request): JsonResponse
    {
        $data =  validateOrExit([
            'problem_id' => 'required|integer',
            'rate_id'    => 'required|integer',
            'text'       => 'required|string|censor',
            'images'     => 'nullable|array|max:8',
            'images.*'   => 'nullable|string',
        ]);

        if (Dispute::whereRateId($data['rate_id'])->whereNotIn('status', [Dispute::STATUS_CLOSED, Dispute::STATUS_CANCELED])->exists()) {
            throw new ErrorException(__('message.dispute_exists'));
        }

        $rate = Rate::with('order:id,user_id')
            ->whereKey($data['rate_id'])
            ->whereIn('status', [Rate::STATUS_ACCEPTED, Rate::STATUS_BUYED])
            ->first(['id', 'user_id', 'chat_id', 'order_id']);

        $auth_user_id = $request->user()->id;
        if (!$rate || !in_array($auth_user_id, [$rate->user_id, $rate->order->user_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        if (! $problem = DisputeProblem::whereKey($data['problem_id'])->active()->first(['days'])) {
            throw new ErrorException(__('message.problem_not_found'));
        }

        # информируем в чат об открытии спора
        Chat::addSystemMessage($rate->chat_id, 'dispute_opened');

        # добавляем доп.данные
        $data['chat_id'] = $rate->chat_id;
        $data['deadline'] = Carbon::now()->addDays($problem->days)->toDateString();

        # отправляем в чат сообщение с текстом спора
        $message = Message::create(Arr::only($data, ['chat_id', 'text', 'images', 'user_id']));
        $data['message_id'] = $message->id;

        # создаем спор
        $dispute = Dispute::create($data);

        # увеличиваем счетчик непрочитанных сообщений
        $dispute->chat()->increment($auth_user_id == $rate->user_id ? 'customer_unread_count' : 'performer_unread_count');

        # броадкастим кол-во непрочитанных сообщений собеседнику чата
        $recipient_id = $auth_user_id == $rate->user_id ? $rate->order->user_id : $rate->user_id;
        Chat::broadcastCountUnreadMessages($recipient_id);

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Получить спор по коду.
     *
     * @param int $id
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function showDisputeById(int $id): JsonResponse
    {
        return self::showDispute(request()->merge(['dispute_id' => $id]));
    }

    /**
     * Получить список споров по фильтру.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function showDispute(Request $request): JsonResponse
    {
        validateOrExit([
            'dispute_id' => 'nullable|integer',
            'order_id'   => 'nullable|integer',
            'route_id'   => 'nullable|integer',
            'rate_id'    => 'nullable|integer',
            'chat_id'    => 'nullable|integer',
            'status'     => 'nullable|in:' . implode(',', array_keys(Dispute::STATUSES)),
        ]);

        $disputes = Dispute::query()
            ->when($request->filled('dispute_id'), function ($query) use ($request) {
                return $query->whereKey($request->get('dispute_id'));
            })
            ->when($request->filled('order_id'), function ($query) use ($request) {
                return $query->whereHas('rate', function ($q) {
                    $q->whereOrderId(request('order_id'));
                });
            })
            ->when($request->filled('route_id'), function ($query) use ($request) {
                return $query->whereHas('rate', function ($q) {
                    $q->whereRouteId(request('route_id'));
                });
            })
            ->when($request->filled('rate_id'), function ($query) use ($request) {
                return $query->whereRateId($request->get('rate_id'));
            })
            ->when($request->filled('chat_id'), function ($query) use ($request) {
                return $query->whereChatId($request->get('chat_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->whereStatus($request->get('status'));
            })
            ->when($request->isNotFilled(['dispute_id', 'order_id', 'route_id', 'rate_id', 'chat_id']), function ($query) use ($request) {
                return $query->whereUserId($request->user()->id);
            })
            ->with([
                'problem',
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW);
                },
                'rate',
                'rate.order',
                'rate.route',
                'chat',
                'message',
                'dispute_closed_reason',
            ])
            ->get();

        return response()->json([
            'status'   => true,
            'disputes' => null_to_blank($disputes),
        ]);
    }

    /**
     * Отменить спор.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function cancelDispute(int $id, Request $request): JsonResponse
    {
        $dispute = Dispute::query()
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->whereNotIn('status', [Dispute::STATUS_CLOSED, Dispute::STATUS_CANCELED])
            ->first();

        if (! $dispute) {
            throw new ErrorException(__('message.dispute_not_exists'));
        }

        # меняем статус спора
        $dispute->status = Dispute::STATUS_CANCELED;
        $dispute->save();

        # информируем в чат об отклонении спора
        Chat::addSystemMessage($dispute->chat_id, 'dispute_canceled');

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Получить справочник "Проблемы спора".
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getProblems(int $id = 0): JsonResponse
    {
        return response()->json(DisputeProblem::getList($id));
    }

    /**
     * Получить количество споров по фильтру.
     * (используется админкой)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDisputesCounter(Request $request): JsonResponse
    {
        $counter = Dispute::query()
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->get('status'));
            })
            ->when($request->filled('admin_user_id', 0), function ($query) use ($request) {
                return $query->where('admin_user_id', $request->get('admin_user_id'));
            })
            ->count();

        return response()->json(['value' => $counter]);
    }
}
