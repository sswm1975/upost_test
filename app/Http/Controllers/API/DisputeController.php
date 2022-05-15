<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Order;
use App\Models\Problem;
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
     * Правила проверки входных данных запроса при сохранении заказа.
     *
     * @return array
     * @throws ValidationException|ValidatorException
     */
    protected static function rules4saveDispute(): array
    {
        return validateOrExit([
            'problem_id'  => 'required|integer|exists:problems,id',
            'rate_id'  => [
                'required',
                'integer',
                function ($attribute, $rate_id, $fail) {
                    $is_permission = Rate::whereKey($rate_id)
                        ->where('chat_id', request('chat_id', 0))
                        ->count();

                    if (! $is_permission) {
                        return $fail(__('message.not_have_permission'));
                    }

                    if (Dispute::whereRateId($rate_id)->count()) {
                        return $fail(__('message.dispute_exists'));
                    }
                }
            ],
            'chat_id'  => [
                'required',
                'integer',
                function ($attribute, $chat_id, $fail) {
                    $is_permission = Chat::whereKey($chat_id)
                        ->where(function($query) {
                            $user_id = request()->user()->id;
                            $query->where('performer_id', $user_id)->orWhere('customer_id', $user_id);
                        })
                        ->count();

                    if (! $is_permission) {
                        return $fail(__('message.not_have_permission'));
                    }
                }
            ],
            'text'     => 'required|string|censor',
            'images'   => 'nullable|array|max:8',
            'images.*' => 'nullable|string',
        ]);
    }

    /**
     * Получить данные спора.
     *
     * @param int $id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function show(int $id): JsonResponse
    {
        $dispute = Dispute::whereKey($id)
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW);
                },
                'closed_user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW);
                },
                'rate',
                'chat',
                'message',
            ])
            ->first();

        if (! $dispute) throw new ErrorException(__('message.dispute_not_found'));

        return response()->json([
            'status'  => true,
            'dispute' => null_to_blank($dispute),
        ]);
    }

    /**
     * Добавить спор.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function add(Request $request): JsonResponse
    {
        $data = $this->rules4saveDispute();

        $days = Problem::find($data['problem_id'])->value('days');
        $data['deadline'] = Carbon::now()->addDays($days)->toDateString();

        $message = Message::create(Arr::only($data, ['chat_id', 'text', 'images', 'user_id']));
        $data['message_id'] = $message->id;

        $dispute = Dispute::create($data);

        $dispute->chat()->update(['lock_status' => Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL]);
        $dispute->rate()->update(['status' => Rate::STATUS_DISPUTE]);

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Изменить статус спора.
     * (доступно только для администратора)
     *
     * @param int $id
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function changeStatus(int $id): JsonResponse
    {
        if (request()->user()->role != User::ROLE_ADMIN) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        $data = validateOrExit([
            'status' => 'required|in:' . implode(',', Dispute::STATUSES),
        ]);

        $affected_rows = Dispute::whereKey($id)->update($data);

        return response()->json(['status' => $affected_rows > 0]);
    }

    /**
     * Взять спор в работу.
     * (доступно только для администратора)
     *
     * @param int $id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function takeOn(int $id): JsonResponse
    {
        if (request()->user()->role != User::ROLE_ADMIN) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        $affected_rows = Dispute::whereKey($id)->update(['status' => Dispute::STATUS_IN_WORK]);

        return response()->json(['status' => $affected_rows > 0]);
    }

    /**
     * Закрыть спор и связанные сущности.
     *
     * @param int $id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function close(int $id): JsonResponse
    {
        # ищем сущности, которые нужно закрыть
        $dispute = Dispute::find($id);
        $chat = Chat::find($dispute->chat_id);
        $rate = Rate::find($dispute->rate_id);
        $order = Order::find($rate->order_id);

        # закрыть диспут может только заказчик, или исполнитель, или админ
        if (! in_array(request()->user()->id, [$chat->performer_id, $chat->customer_id]) && request()->user()->role != User::ROLE_ADMIN) {
            throw new ErrorException(__('message.not_have_permission'));
        }

        # закрываем (меняем статусы)
        $order->update(['status' => Order::STATUS_SUCCESSFUL]);
        $rate->update(['status' => Rate::STATUS_DONE]);
        $chat->update(['status' => Chat::STATUS_CLOSED]);
        $dispute->update(['status' => Dispute::STATUS_CLOSED]);

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Изменить дату дедлайна спора.
     *
     * @param int $id
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function changeDeadline(int $id): JsonResponse
    {
        $data = validateOrExit([
            'deadline' => 'required|date_format:Y-m-d|after_or_equal:'.date('Y-m-d'),
        ]);

        $affected_rows = Dispute::whereKey($id)->update($data);

        return response()->json(['status' => $affected_rows > 0]);
    }

    /**
     * Получить справочник проблем для спора или выбранной проблемы.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getProblems(int $id = 0): JsonResponse
    {
        return response()->json(Problem::getList($id));
    }
}
