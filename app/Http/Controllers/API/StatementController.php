<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Rate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exceptions\ValidatorException;
use Illuminate\Validation\ValidationException;
use App\Models\Statement;

class StatementController extends Controller
{
    /** @var string  Тип пользователя "Заказчик" */
    const USER_CUSTOMER = 'Customer';

    /** @var string Тип пользователя  "Исполнитель" */
    const USER_PERFORMER = 'Performer';

    /** @var int Максимальное количество пролонгаций, которых может сделать заказчик */
    const MAX_COUNT_PROLONGATIONS = 3;

    /**
     * Создать заявление.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function addStatement(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'rate_id' => 'required|integer|owner_rate|exists:jobs,rate_id,job_status,work',
        ]);

        # узнаем типа пользователя по отношению к заказу: Заказчик или Исполнитель
        # (юзер, який створив чат, то це власник заказу (замовник), інакше - виконавець)
        $chat = Chat::whereRateId($data['rate_id'])->oldest('chat_id')->first();
        if (!$chat) {
            throw new ErrorException(__('message.chat_not_found'));
        }
        $user_type = $request->user()->user_id == $chat->user_id ? self::USER_CUSTOMER : self::USER_PERFORMER;

        # в зависимости от типа пользователя выполняем доп.проверки
        $method = 'addValidate4' . $user_type;
        if (!method_exists(self::class, $method)) {
            throw new ErrorException("Method {$method} not found", 500);
        }
        call_user_func([self::class, $method], $data['rate_id'], $request->user()->user_id);

        # все проверки прошли, создаем заявление на пролонгацию
        $statement = Statement::create($data);

        return response()->json([
            'status' => true,
            'message' => __('message.statement_created'),
            'result' => null_to_blank($statement),
        ]);
    }

    /**
     * Дополнительная проверка при создании заявления Заказчиком.
     * (не должно быть активных заявлений и общее кол-во выполненных заявлений не должно быть больше установленного лимита)
     *
     * @param int $rate_id
     * @param int $user_id
     * @throws ErrorException
     */
    public function addValidate4Customer(int $rate_id, int $user_id)
    {
        $statements = Statement::where([
            'user_id' => $user_id,
            'rate_id' => $rate_id,
        ])->get(['id', 'status']);

        if ($statements->where('status', Statement::STATUS_ACTIVE)->count()) {
            throw new ErrorException(__('message.exists_active_statement'));
        };

        if ($statements->where('status', Statement::STATUS_DONE)->count() >= self::MAX_COUNT_PROLONGATIONS) {
            throw new ErrorException(__('message.statement_max_limit'));
        }
    }

    /**
     * Дополнительная проверка при создании заявления Исполнителем.
     * (дедлайн у ставки/заказа должен наступить)
     *
     * @param int $rate_id
     * @param int $user_id
     * @throws ErrorException
     */
    public function addValidate4Performer(int $rate_id, int $user_id)
    {
        $rate = Rate::whereKey($rate_id)->first(['rate_id', 'rate_deadline']);

        if (Carbon::today()->toDateString() < $rate->rate_deadline) {
            throw new ErrorException(__('message.deadline_not_arrived'));
        };
    }

    /**
     * Отклонить заявление.
     *
     * @param int $id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function rejectStatement(int $id): JsonResponse
    {
        $statement = Statement::whereKey($id)
            ->whereStatus(Statement::STATUS_ACTIVE)
            ->first();

        if (!$statement) throw new ErrorException(__('message.statement_not_found'));

        $statement->update(['status' => Statement::STATUS_REJECTED]);

        return response()->json([
            'status' => true,
            'message' => __('message.statement_rejected'),
        ]);
    }

    /**
     * Подтвердить заявление.
     *
     * @param int $id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function acceptStatement(int $id): JsonResponse
    {
        $statement = Statement::whereKey($id)
            ->whereStatus(Statement::STATUS_ACTIVE)
            ->first();

        if (!$statement) throw new ErrorException(__('message.statement_not_found'));

        $rate = Rate::find($statement->rate_id);

        $prolongation_date = Carbon::parse($rate->rate_deadline)->addDays(3)->toDateString();

        $rate->order()->update(['order_deadline' => $prolongation_date]);
        $rate->update(['rate_deadline' => $prolongation_date]);
        $statement->update(['status' => Statement::STATUS_DONE]);

        return response()->json([
            'status' => true,
            'message' => __('message.statement_accepted'),
        ]);
    }
}
