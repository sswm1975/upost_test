<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RateController extends Controller
{
    /**
     * Правила проверки входных данных запроса при создании ставки.
     * (создать/править ставку может только владелец активного маршрута; заказ должен быть активным).
     *
     * @return array
     */
    protected static function rules4saveRate(): array
    {
        return  [
            'route_id' => 'required|integer|exists:routes,id,status,active,user_id,'.request()->user()->id,
            'order_id' => 'required|integer|exists:orders,id,status,active',
            'deadline' => 'required|date|after_or_equal:'.date('Y-m-d'),
            'amount'   => 'required|numeric',
            'currency' => 'required|in:' . implode(',', config('app.currencies')),
            'comment'  => 'required|string|censor|max:1000',
        ];
    }

    /**
     * Создать ставку.
     *
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function addRate(): JsonResponse
    {
        $data = validateOrExit(self::rules4saveRate());

        # запрещаем дублировать ставку
        $is_double = Rate::active()->where(Arr::only($data, ['user_id', 'order_id', 'route_id']))->count();
        if ($is_double) throw new ErrorException(__('message.rate_exists'));

        Rate::create($data);

        return response()->json(['status' => true]);
    }

    /**
     * Изменить ставку.
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function updateRate(int $rate_id): JsonResponse
    {
        if (! $rate = Rate::isOwnerByKey($rate_id)->first(['id'])) {
            throw new ErrorException(__('message.rate_not_found'));
        }
        $data = validateOrExit(self::rules4saveRate());

        $affected = $rate->update($data);

        return response()->json(['status' => $affected]);
    }

    /**
     * Просмотр ставки.
     * (доступ к ставке только у владельцев маршрута или заказа)
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function showRate(int $rate_id): JsonResponse
    {
        $rate = Rate::query()
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW)->withCount('successful_orders');
                },
                'route.from_country',
                'route.from_city',
                'route.to_country',
                'route.to_city',
            ])
            ->whereKey($rate_id)
            ->where(function ($query) {
                return $query->owner()->orWhereHas('order', function($query) {
                    $query->owner();
                });
            })
            ->first();

        if (! $rate) throw new ErrorException(__('message.rate_not_found'));

        # если ставка не прочитана и ставку смотрит владелец заказа, то устанавливаем признак просмотра ставки
        if (!$rate->is_read && $rate->user_id <> request()->user()->id) {
            $rate->is_read = true;
            $rate->save();
        }

        return response()->json([
            'status' => true,
            'result' => null_to_blank($rate),
        ]);
    }

    /**
     * Отменить ставку.
     * (доступ имеет только владелец ставки; ставка должна быть активной)
     *
     * @param int $rate_id
     * @return JsonResponse
     */
    public function cancelRate(int $rate_id): JsonResponse
    {
        $affected_rows = Rate::IsOwnerByKey($rate_id)->update(['status' => Rate::STATUS_CANCELED]);

        return response()->json([
            'status' => $affected_rows > 0,
        ]);
    }

    /**
     * Удалить ставку.
     * (доступ имеет только владелец ставки; ставка должна быть активной или отмененной)
     *
     * @param int $rate_id
     * @return JsonResponse
     */
    public function deleteRate(int $rate_id): JsonResponse
    {
        $affected = Rate::IsOwnerByKey($rate_id, [Rate::STATUS_ACTIVE, Rate::STATUS_CANCELED])->delete();

        return response()->json([
            'status' => $affected,
        ]);
    }

    /**
     * Принять ставку.
     * (доступ имеет только владелец активного заказа; ставка должна быть активной)
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException|\Throwable
     */
    public function acceptRate(int $rate_id): JsonResponse
    {
        $rate = Rate::query()
            ->whereKey($rate_id)
            ->active()
            ->whereHas('order', function($query) {
                $query->owner()->active();
            })
            ->first(['id']);

        if (! $rate) throw new ErrorException(__('message.rate_not_found'));

        /* TODO Подтверждение ставки: Нужно добавить проверку по наличию транзакции */

        DB::beginTransaction();
        try {
            $rate->status = Rate::STATUS_ACCEPTED;
            $rate->is_read = true;
            $rate->save();
            $rate->order()->update(['status' => Order::STATUS_IN_WORK]);
            DB::commit();
            $status = true;
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
        }

        return response()->json([
            'status' => $status,
            'result' => [__('message.rate_accepted')],
        ]);
    }

    /**
     * Оклонить ставку.
     * (доступ имеет только владелец заказа; ставка должна быть активной)
     *
     * @param int $rate_id
     * @return JsonResponse
     */
    public function rejectRate(int $rate_id): JsonResponse
    {
        $affected_rows = Rate::query()
            ->whereKey($rate_id)
            ->active()
            ->whereHas('order', function($query) {
                $query->owner();
            })
            ->update(['status' => Rate::STATUS_REJECTED, 'is_read' => 1]);

        return response()->json([
            'status' => $affected_rows > 0,
        ]);
    }
}
