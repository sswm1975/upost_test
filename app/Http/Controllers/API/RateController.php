<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Order;
use App\Models\Rate;
use App\Models\Transaction;
use App\Models\User;
use App\Modules\Liqpay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
            'deadline' => 'required|date_format:Y-m-d|after_or_equal:'.date('Y-m-d'),
            'amount'   => 'required|numeric',
            'currency' => 'required|in:' . implode(',', config('app.currencies')),
            'comment'  => 'required|string|censor|max:1000',
        ];
    }

    /**
     * Создать ставку.
     * (имеет право только владелец маршрута)
     *
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function addRate(): JsonResponse
    {
        $data = validateOrExit(self::rules4saveRate());

        # запрещаем дублировать ставку
        $is_double = Rate::active()->where(Arr::only($data, ['user_id', 'order_id', 'route_id']))->exists();
        if ($is_double) {
            throw new ErrorException(__('message.rate_add_double'));
        }

        # проверяем запрет на превышение суммы вознаграждения, установленного на заказе
        $order = Order::find($data['order_id'], ['user_price_usd', 'not_more_price', 'user_id']);
        $amount_usd = convertPriceToUsd($data['amount'], $data['currency']);
        if ($order->not_more_price && $amount_usd > $order->user_price_usd) {
            throw new ErrorException(__('message.rate_exists_limit_user_price'));
        }

        # создаем ставку
        $rate = Rate::create($data);

        return response()->json([
            'status'  => true,
            'rate'    => null_to_blank($rate),
        ]);
    }

    /**
     * Изменить ставку.
     * (имеет право только владелец маршрута)
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function updateRate(int $rate_id): JsonResponse
    {
        if (! $rate = Rate::byKeyForRateOwner($rate_id)->first()) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # общая валидация параметров
        $data = validateOrExit(self::rules4saveRate());

        # изменять маршрут или заказ запрещено
        if ($rate->route_id <> $data['route_id'] || $rate->order_id <> $data['order_id']) {
            throw new ErrorException(__('message.update_denied'));
        }

        # проверяем запрет на превышение суммы вознаграждения, установленного на заказе
        $order = Order::find($data['order_id'], ['user_price_usd', 'not_more_price', 'user_id']);
        $amount_usd = convertPriceToUsd($data['amount'], $data['currency']);
        if ($order->not_more_price && $amount_usd > $order->user_price_usd) {
            throw new ErrorException(__('message.rate_exists_limit_user_price'));
        }

        # конвертируем число в вещественную строку (если суммы одинаковые в таблице и во входном параметре, то обновление данных в таблице не будет)
        $data['amount'] = number_format($data['amount'], 2);

        # изменяем ставку
        $rate->update($data);

        return response()->json([
            'status'  => true,
            'rate'    => null_to_blank($rate),
            'changes' => null_to_blank($rate->getChanges()),
        ]);
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
        $rate = Rate::whereKey($rate_id)
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW)->withCount('successful_orders');
                },
                'route.from_country',
                'route.from_city',
                'route.to_country',
                'route.to_city',
            ])
            ->where(function ($query) {
                return $query->owner()->orWhereHas('order', function($query) {
                    $query->owner();
                });
            })
            ->first();

        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # если ставка не прочитана заказчиком и ставку смотрит владелец заказа, то устанавливаем признак просмотра ставки
        if (!$rate->is_read && $rate->user_id <> request()->user()->id) {
            $rate->is_read = true;
            $rate->save();
        }

        return response()->json([
            'status' => true,
            'rate'   => null_to_blank($rate),
        ]);
    }

    /**
     * Отменить ставку.
     * (доступ имеет только владелец ставки (исполнитель); ставка должна быть активной)
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function cancelRate(int $rate_id): JsonResponse
    {
        if (! $rate = Rate::byKeyForRateOwner($rate_id)->first(['id', 'status'])) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        $rate->status = Rate::STATUS_CANCELED;
        $rate->save();

        return response()->json(['status' => true]);
    }

    /**
     * Удалить ставку.
     * (доступ имеет только владелец ставки; ставка должна быть активной или отмененной)
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function deleteRate(int $rate_id): JsonResponse
    {
        $rate = Rate::byKeyForRateOwner($rate_id, [Rate::STATUS_ACTIVE, Rate::STATUS_CANCELED])->first(['id']);
        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        $rate->delete();

        return response()->json(['status' => true]);
    }

    /**
     * Отклонить ставку.
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function rejectRate(int $rate_id): JsonResponse
    {
        # доступ к операции имеет только владелец заказа; ставка должна быть в статусе active; статус заказа - любой
        $rate = Rate::byKeyForOrderOwner($rate_id)->first(['id', 'status', 'is_read']);

        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # такое сохранение быстрее, чем через update()
        $rate->status = Rate::STATUS_REJECTED;
        $rate->is_read = true;
        $rate->save();

        return response()->json(['status' => true]);
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
        $rate = Rate::byKeyForOrderOwner($rate_id, [Rate::STATUS_ACTIVE], [Order::STATUS_ACTIVE])
            ->with([
                'order:id,name,price,currency,price_usd,products_count,status,user_id,images',
                'route.user:id,name,surname,photo,scores_count,reviews_count,status,gender,birthday,validation,last_active,register_date',
                'route.to_country',
                'route.to_city',
            ])
            ->first();

        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        /* TODO Расчет суммы для оплаты: Нужно реализовать конвертацию цены и дохода; добавить пошлину за ввоз; суммировать все расчеты */
        $tax = 0;
        $fee = 0;
        $amount = $rate->order->price + $rate->amount + $tax + $fee;
        $currency = 'UAH';

        $user = $request->user();
        $callback_url = $request->get('callback_url', config('app.wordpress_url'));

        $params = Liqpay::create_params(
            $user->id,
            $user->full_name,
            $rate_id,
            $amount,
            $currency,
            'Оплата заказа "' . $rate->order->name . '"',
            'ru',
            $callback_url,
        );

        $payment = array_merge($params, [
            'price'    => 1 * $rate->order->price,
            'profit'   => 1 * $rate->amount,
            'tax'      => 1 * $tax,
            'fee'      => 1 * $fee,
            'amount'   => 1 * $amount,
            'currency' => $currency,
        ]);

        return response()->json([
            'status'  => true,
            'rate'    => $rate,
            'payment' => $payment,
        ]);
    }

    /**
     * Обработать результат оплаты от Liqpay и сохранение транзакции.
     * (описание см. https://www.liqpay.ua/documentation/api/callback)
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function callbackPayment(Request $request): JsonResponse
    {
        $data = $request->get('data');
        $signature = $request->get('signature');

        if (empty($data) || empty($signature) ) {
            throw new ErrorException('Нет данных в data и/или в signature');
        }

        $response = Liqpay::decode_responce($data, $signature);
        if (isset($response['error'])) {
            throw new ErrorException($response['error']);
        }

        $liqpay = $response['data'];
        $rate_id = $liqpay['info']['rate_id'];

        Log::info($liqpay);

        if (! in_array($liqpay['status'], ['success', 'sandbox'])) {
            throw new ErrorException('Статус платежа не равен "success" или "sandbox", получен статус "'.$liqpay['status'].'"');
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

        $rate = Rate::find($rate_id);
        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # ищем существующий чат или создаем новый
        $chat = Chat::searchOrCreate($rate->route_id, $rate->order_id, $rate->user_id, $liqpay['info']['user_id']);

        # информируем в чат, что заказчик оплатил заказ.
        Chat::addSystemMessage($chat->id, 'customer_paid_order');

        # обновляем данные по ставке
        $rate->status = Rate::STATUS_ACCEPTED;
        $rate->is_read = true;
        $rate->chat_id = $chat->id;
        $rate->save();
        $rate->order()->update(['status' => Order::STATUS_IN_WORK]);

        return response()->json([
            'status' => true,
            'liqpay' => null_to_blank($liqpay),
            'rate'   => null_to_blank($rate),
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
        $rate = Rate::byKeyForRateOwner($rate_id, [Rate::STATUS_ACCEPTED])->first();
        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        $data = validateOrExit(['images' => 'required|array|max:5']);

        $rate->images = $data['images'];
        $rate->status = Rate::STATUS_BUYED;
        $rate->save();

        # информируем в чат, что исполнитель купил товар.
        Chat::addSystemMessage($rate->chat_id, 'performer_buyed_product');

        return response()->json([
            'status' => true,
            'rate'   => null_to_blank($rate),
        ]);
    }

    /**
     * Получение товара заказчиком.
     * - операция только для владельца заказа
     * - ставка должна быть в статусе buyed
     * - заказ должен быть в статусе in_work
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function successfulRate(int $rate_id): JsonResponse
    {
        $rate = Rate::byKeyForOrderOwner($rate_id, [Rate::STATUS_BUYED], [Order::STATUS_IN_WORK])->first();
        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # меняем статусы на Ставке и Заказе
        $rate->status = Rate::STATUS_SUCCESSFUL;
        $rate->save();
        $rate->order()->update(['status' => Order::STATUS_SUCCESSFUL]);

        # информируем в чат, что Заказчик получил товар.
        Chat::addSystemMessage($rate->chat_id, 'customer_received_order');

        return response()->json([
            'status' => true,
            'rate'   => null_to_blank($rate),
        ]);
    }

    /**
     * Получить ставки по выбранному заказу.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     */
    public function showRatesByOrder(int $order_id, Request $request):JsonResponse
    {
        $rates = Rate::whereOrderId($order_id)
            ->when($request->filled('user_id'), function ($query) use ($request) {
                return $query->whereUserId($request->input('user_id'));
            })
            ->with([
                'user:' . implode(',', User::FIELDS_FOR_SHOW),
                'route.from_country',
                'route.from_city',
            ])
            ->oldest()
            ->get();

        return response()->json([
            'status' => true,
            'rates'  => null_to_blank($rates),
        ]);
    }
}
