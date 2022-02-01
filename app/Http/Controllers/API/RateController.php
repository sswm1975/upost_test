<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rate;
use App\Models\Transaction;
use App\Models\User;
use App\Modules\Liqpay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $affected_rows = Rate::isOwnerByKey($rate_id)->update(['status' => Rate::STATUS_CANCELED]);

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
        $affected = Rate::isOwnerByKey($rate_id, [Rate::STATUS_ACTIVE, Rate::STATUS_CANCELED])->delete();

        return response()->json([
            'status' => $affected,
        ]);
    }

    /**
     * Отклонить ставку.
     * (доступ имеет только владелец заказа; ставка должна быть в статусе active)
     *
     * @param int $rate_id
     * @return JsonResponse
     */
    public function rejectRate(int $rate_id): JsonResponse
    {
        $affected_rows = Rate::whereKey($rate_id)
            ->active()
            ->whereHas('order', function($query) {
                $query->owner();
            })
            ->update(['status' => Rate::STATUS_REJECTED, 'is_read' => 1]);

        return response()->json([
            'status' => $affected_rows > 0,
        ]);
    }

    /**
     * Подготовить данные для оплаты по выбранной ставке (формирование параметров для Liqpay-платежа).
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function preparePayment(int $rate_id, Request $request): JsonResponse
    {
        # ищем активную ставку, где владельцем активного заказа является авторизированный пользователь
        $rate = Rate::whereKey($rate_id)
            ->with([
                'order:id,name,price,currency,price_usd,products_count,status,images',
                'route.user:id,name,surname,photo,creator_rating,status,gender,birthday,validation,last_active,register_date',
                'route.to_country',
                'route.to_city',
            ])
            ->active()
            ->whereHas('order', function($query) {
                $query->owner()->active();
            })
            ->first();

        if (! $rate) throw new ErrorException(__('message.rate_not_found'));

        /* TODO Расчет суммы для оплаты: Нужно реализовать конвертацию цены и дохода; добавить пошлину за ввоз; суммировать все расчеты */
        $amount = $rate->order->price + $rate->amount;

        $user = $request->user();

        $params = Liqpay::create_params(
            $user->id,
            $user->user_surname . ' ' . $user->user_name,
            $rate_id,
            $amount,
            'UAH',
            'Оплата заказа "' . $rate->order->name . '"',
            'ru',
        );

        return response()->json([
            'status'  => true,
            'rate'    => $rate,
            'payment' => $params,
        ]);
    }

    /**
     * Обработать результат оплаты от Liqpay и сохранение транзакции.
     * (описание см. https://www.liqpay.ua/documentation/api/callback)
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|\Throwable
     */
    public function callbackPayment(Request $request): JsonResponse
    {
        $data = $request->get('data');
        $signature = $request->get('signature');

        if (empty($data) || empty($signature) ) {
            return response()->json([
                'status' => false,
                'error'  => 'Нет данных в data и/или в signature'
            ]);
        }

        $response = Liqpay::decode_responce($data, $signature);
        if (!$response['status']) {
            return response()->json($response);
        }

        $liqpay = $response['data'];
        $rate_id = $liqpay['info']['rate_id'];

        Log::info($liqpay);

        if (! in_array($liqpay['status'], ['success', 'sandbox'])) {
            return response()->json([
                'status' => false,
                'error'  => 'Статус платежа не равен "success" или "sandbox", получен статус "'.$liqpay['status'].'"',
            ]);
        }

        Transaction::create([
            'user_id'     => $liqpay['info']['user_id'],
            'rate_id'     => $rate_id,
            'amount'      => $liqpay['amount'],
            'description' => $liqpay['description'],
            'status'      => $liqpay['status'],
            'response'    => $liqpay,
            'payed_at'    => gmdate('Y-m-d H:i:s', strtotime("+2 hours", $liqpay['end_date'] / 1000)),
        ]);

        $rate = Rate::find($rate_id, ['id', 'order_id', 'status', 'is_read']);
        if (! $rate) throw new ErrorException(__('message.rate_not_found'));

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
            'data'   => $liqpay,
        ]);
    }

    /**
     * Подтверждение покупки товара исполнителем (Товар по ставке куплен).
     * (доступ к операции имеет только владелец маршрута; ставка должна быть в статусе accepted)
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function buyedRate(int $rate_id): JsonResponse
    {
        if (! $rate = Rate::isOwnerByKey($rate_id, [Rate::STATUS_ACCEPTED])->first(['id'])) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        $data = validateOrExit([
            'images' => 'required|array|max:5',
        ]);

        $affected = $rate->update([
            'images' => $data['images'],
            'status' => Rate::STATUS_BUYED,
        ]);

        return response()->json(['status' => $affected]);
    }

    /**
     * Подтверждение покупки товара заказчиком.
     * - операция только для владельца заказа
     * - ставка должна быть в статусе accepted
     * - заказ должен быть в статусе in_work
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function approvedRate(int $rate_id): JsonResponse
    {
        $rate = Rate::byKeyForOwnerOrder($rate_id, [Rate::STATUS_BUYED], [Order::STATUS_IN_WORK])->first(['id']);

        if (! $rate) throw new ErrorException(__('message.rate_not_found'));

        $rate->status = Rate::STATUS_APPROVED;
        $rate->save();

        return response()->json(['status' => true]);
    }
}
