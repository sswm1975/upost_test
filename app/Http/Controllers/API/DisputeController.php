<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\DisputeProblem;
use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Rate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        # это не дубль?
        if (Dispute::whereRateId($data['rate_id'])->whereNotIn('status', [Dispute::STATUS_CLOSED, Dispute::STATUS_CANCELED])->exists()) {
            throw new ErrorException(__('message.dispute_exists'));
        }

        # ищем проблему и узнаем кол-во дней на её решение
        if (! $problem = DisputeProblem::whereKey($data['problem_id'])->active()->first(['days'])) {
            throw new ErrorException(__('message.problem_not_found'));
        }

        # ищем ставку
        $rate = Rate::with('order:id,user_id,name')
            ->whereKey($data['rate_id'])
            ->whereIn('status', [Rate::STATUS_ACCEPTED, Rate::STATUS_BUYED])
            ->first(['id', 'user_id', 'chat_id', 'order_id']);

        # проверяем, что ставка существует и инициатор спора является её участником
        $auth_user_id = $request->user()->id;
        if (!$rate || !in_array($auth_user_id, [$rate->user_id, $rate->order->user_id])) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        # добавляем доп.данные
        $data['chat_id'] = $rate->chat_id;
        $data['deadline'] = Carbon::now()->addDays($problem->days)->toDateString();

        # создаем спор
        $dispute = Dispute::create($data);

        # информируем в чат об открытии спора
        Message::create([
            'chat_id'    => $rate->chat_id,
            'user_id'    => $auth_user_id,
            'dispute_id' => $dispute->id,
            'text'       => 'dispute_opened',
            'images'     => $data['images'] ?? [],
        ]);

        # увеличиваем счетчик непрочитанных сообщений
        $dispute->chat()->increment($auth_user_id == $rate->user_id ? 'customer_unread_count' : 'performer_unread_count');

        # броадкастим кол-во непрочитанных сообщений собеседнику чата
        $recipient_id = $auth_user_id == $rate->user_id ? $rate->order->user_id : $rate->user_id;
        Chat::broadcastCountUnreadMessages($recipient_id);

        # создаем уведомление "Открыт спор"
        if (active_notice_type($notice_type = NoticeType::DISPUTE_OPENED)) {
            Notice::create([
                'user_id'     => $recipient_id,
                'notice_type' => $notice_type,
                'object_id'   => $rate->order->id,
                'data'        => ['order_name' => $rate->order->name, 'rate_id' => $rate->id, 'dispute_id' => $dispute->id]
            ]);
        }

        return response()->json([
            'status'  => true,
            'dispute' => null_to_blank($dispute),
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
            ->whereIn('status', Dispute::STATUSES_ACTING)
            ->first();

        if (! $dispute) {
            throw new ErrorException(__('message.dispute_not_exists'));
        }

        # меняем статус спора
        $dispute->status = Dispute::STATUS_CANCELED;
        $dispute->save();

        # информируем в чат об отмене спора
        Chat::addSystemMessage($dispute->chat_id, 'dispute_canceled');

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Получить справочник "Проблемы спора".
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function getProblems(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'rate_id' => 'required|integer',
        ]);

        if (! $rate = Rate::find($data['rate_id'], ['id', 'user_id', 'status'])) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        $initiator = $rate->user_id == $request->user()->id ? PERFORMER : CUSTOMER;

        $problems = DisputeProblem::active()
            ->where('initiator', '=', $initiator)
            ->where('rate_status', '=', $rate->status)
            ->language()
            ->addSelect('id')
            ->get()
            ->toArray();

        return response()->json([
            'status'   => true,
            'problems' => $problems,
        ]);
    }
}
