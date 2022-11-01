<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Order;
use App\Models\OrderDeduction;
use App\Models\Payment;
use App\Models\Rate;
use App\Models\Route;
use App\Models\Transaction;
use App\Models\User;
use App\Modules\Liqpay;
use App\Modules\PayPal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
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
            'deadline' => 'required|date_format:Y-m-d|after_or_equal:'.date('Y-m-d'),
            'amount'   => 'required|numeric',
            'currency' => 'required|in:' . implode(',', config('app.currencies')),
            'comment'  => 'nullable|string|censor|max:1000',
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
        $rate = Rate::create(array_merge($data, compact('amount_usd')));

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
        $data['amount_usd'] = number_format($amount_usd, 2);

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
                'chat' => function($query) {
                    $query->withoutAppends();
                },
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

        # если новая ставка не просмотрена заказчиком и ставку смотрит владелец заказа, то устанавливаем флаг просмотра ставки заказчиком
        if (!$rate->viewed_by_customer && $rate->user_id <> request()->user()->id) {
            $rate->viewed_by_customer = true;
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
        $rate = Rate::byKeyForOrderOwner($rate_id)->first(['id', 'status', 'viewed_by_customer']);

        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # такое сохранение быстрее, чем через update()
        $rate->status = Rate::STATUS_REJECTED;
        $rate->viewed_by_customer = true;
        $rate->save();

        return response()->json(['status' => true]);
    }

    /**
     * Оплатить через PayPal по выбранной ставке.
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function purchase(int $rate_id, Request $request): JsonResponse
    {
        # ищем активную ставку, где владельцем активного заказа является авторизированный пользователь
        $rate = Rate::byKeyForOrderOwner($rate_id, [Rate::STATUS_ACTIVE], [Order::STATUS_ACTIVE])
            ->with([
                'order',
                'route.to_country',
                'route.to_city',
            ])
            ->first();

        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        /* TODO Расчет суммы для оплаты: Нужно реализовать конвертацию цены и дохода; добавить пошлину за ввоз; суммировать все расчеты */
        $order_amount = $rate->order->price_usd * $rate->order->products_count;
        $delivery_amount = $rate->amount_usd;
        $payment_service_fee = 0;
        $export_tax = 0;
        $company_fee = round($order_amount * config('service_fee_percent') / 100, 2);
        $total_amount = $order_amount + $delivery_amount + $payment_service_fee + $export_tax + $company_fee;

        $user = $request->user();

        $transaction = Transaction::create([
            'user_id'             => $user->id,
            'rate_id'             => $rate_id,
            'amount'              => $total_amount,
            'order_amount'        => $order_amount,
            'delivery_amount'     => $delivery_amount,
            'payment_service_fee' => $payment_service_fee,
            'export_tax'          => $export_tax,
            'company_fee'         => $company_fee,
            'description'         => $rate->order->name,
            'status'              => 'new',
        ]);

        $paypal = new PayPal;
        $params = [
            'amount'          => $total_amount,
            'currency'        => 'USD',
            'items' => [
                [
                    'name'     => $rate->order->name,
                    'price'    => $total_amount,
                    'quantity' => 1
                ],
            ],
            'returnUrl' => route('purchase_success', ['transaction_id' => $transaction->id]),
            'cancelUrl' => route('purchase_error', ['transaction_id' => $transaction->id]),
        ];
        $transaction->purchase_params = $params;
        $transaction->save();

        try {
            $response = $paypal->purchase($params);
            $transaction->purchase_response = $response->getData();
            $transaction->status = 'created';
            $transaction->save();

            if ($response->isRedirect()) {
                $transaction->purchase_redirect_url = $response->getRedirectUrl();
                $transaction->save();

                return response()->json([
                    'status' => true,
                    'url'    => $response->getRedirectUrl(),
                ]);
            } else {
                # платеж не прошел
                $transaction->purchase_error = $response->getMessage();
                $transaction->status = 'failed';
                $transaction->save();

                throw new ErrorException($response->getMessage());
            }
        } catch(\Exception $e) {
            $transaction->purchase_exception = $e->getCode() . ' ' . $e->getMessage();
            $transaction->status = 'exception';
            $transaction->save();

            throw new ErrorException($e->getMessage());
        }
    }

    /**
     * Завершение обработки PayPal транзакции (после того, как транзакция будет одобрена, её нужно завершить).
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     */
    public function purchaseSuccess(Request $request)
    {
        $callback_url = config('app.wordpress_url');

        if ($request->missing(['paymentId', 'PayerID', 'transaction_id'])) {
            Log::channel('paypal')->error('[purchaseSuccess] Отсутствуют обязательные параметры: paymentId or PayerID or transaction_id (Транзакция отклонена)!');
            Log::channel('paypal')->debug('[purchaseSuccess] Параметры запроса:');
            Log::channel('paypal')->debug($request->all());

            return redirect($callback_url . '?' . http_build_query([
                'status' => false,
                'error'  => '[purchaseSuccess] Транзакция отклонена (отсутствуют обязательные параметры)!',
            ]));
        }

        $transaction_id = $request->get('transaction_id');
        $transaction = Transaction::find($transaction_id);
        if (! $transaction) {
            Log::channel('paypal')->error("[purchaseSuccess] Транзакция с кодом {$transaction_id} не найдена!");

            return redirect($callback_url . '?' . http_build_query([
                'status' => false,
                'error'  => "[purchaseSuccess] Транзакция с кодом {$transaction_id} не найдена!",
            ]));
        }

        $paypal = new PayPal;

        $response = $paypal->complete($request->get('PayerID'), $request->get('paymentId'));
        if (! $response->isSuccessful()) {
            $transaction->complete_error = $response->getMessage();
            $transaction->status = 'not_successful';
            $transaction->save();

            return redirect($callback_url . '?' . http_build_query([
                'status' => false,
                'error'  => $response->getMessage(),
            ]));
        }

        # КЛИЕНТ УСПЕШНО ОПЛАТИЛ #
        $payment = $response->getData();

        $transaction->complete_response = $payment;
        $transaction->status = $payment['state'];
        $transaction->save();

        # ищем активную ставку
        $rate = Rate::whereKey($transaction->rate_id)->active()->first();
        if (! $rate) {
            return redirect($callback_url . '?' . http_build_query([
                'status' => false,
                'error'  => __('message.rate_not_found'),
            ]));
        }

        # ищем существующий чат или создаем новый
        $chat = Chat::searchOrCreate($rate->route_id, $rate->order_id, $rate->user_id, $transaction->user_id);

        # обновляем данные по ставке
        $rate->status = Rate::STATUS_ACCEPTED;
        $rate->viewed_by_customer = true;
        $rate->chat_id = $chat->id;
        $rate->save();
        $rate->order()->update(['status' => Order::STATUS_IN_WORK]);

        # информируем в чат, что заказчик оплатил заказ.
        Chat::addSystemMessage($chat->id, 'customer_paid_order');

        # создаем уведомление "Ставка принята" для Путешественника
        if (active_notice_type($notice_type = NoticeType::RATE_ACCEPTED)) {
            Notice::create([
                'user_id'     => $rate->user_id,
                'notice_type' => $notice_type,
                'object_id'   => $rate->order_id,
                'data'        => ['order_name' => $rate->order->name, 'rate_id' => $rate->id],
            ]);
        }

        return redirect($callback_url . '?' . http_build_query([
            'return-url-from-rate' => $transaction->rate_id,
            'status'     => true,
            'payment_id' => $payment['id'],
        ]));
    }

    /**
     * Пользователь отменил PayPal платеж.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     */
    public function purchaseError(Request $request)
    {
        $callback_url = config('app.wordpress_url');

        if ($request->missing('transaction_id')) {
            $error = '[purchaseError] Отсутствует обязательный параметр transaction_id (Платёж отклонён)!';
            Log::channel('paypal')->error($error);
            Log::channel('paypal')->debug('[purchaseError] Параметры запроса:');
            Log::channel('paypal')->debug($request->all());

            return redirect($callback_url . '?' . http_build_query([
                'status' => false,
                'error'  => $error,
            ]));
        }

        $transaction_id = $request->get('transaction_id');
        $transaction = Transaction::find($transaction_id);
        if (! $transaction) {
            $error = "[purchaseError] Транзакция с кодом {$transaction_id} не найдена (Платёж отклонён)!";
            Log::channel('paypal')->error($error);

            return redirect($callback_url . '?' . http_build_query([
                'status' => false,
                'error'  => $error,
            ]));
        }

        $transaction->complete_error = $request->all();
        $transaction->status = 'canceled';
        $transaction->save();

        return redirect($callback_url . '?' . http_build_query([
            'status'  => true,
            'message' => 'Пользователь отменил платеж.',
        ]));
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
                'order',
                'route.user:id,name,surname,photo,scores_count,reviews_count,status,gender,birthday,validation,last_active,register_date',
                'route.to_country',
                'route.to_city',
            ])
            ->first();

        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        /* TODO Расчет суммы для оплаты: Нужно реализовать конвертацию цены и дохода; добавить пошлину за ввоз; суммировать все расчеты */
        $order_amount = $rate->order->products_count * $rate->order->price;
        $delivery_amount = 1 * $rate->amount;
        $export_tax = 0;
        $liqpay_fee = round($order_amount * config('liqpay_percent') / 100, 2);
        $service_fee = round($rate->order->price * config('service_fee_percent') / 100, 2);
        $total_amount = $rate->order->price + $rate->amount + $export_tax + $liqpay_fee + $service_fee;
        $currency = 'UAH';

        $user = $request->user();
        $callback_url = $request->get('callback_url', config('app.wordpress_url'));

        $info = [
            'user_id'         => $user->id,
            'rate_id'         => $rate_id,
            'order_amount'    => $rate->order->products_count * $rate->order->price,
            'delivery_amount' => $delivery_amount,
            'export_tax'      => $export_tax,
            'liqpay_fee'      => $liqpay_fee,
            'service_fee'     => $service_fee,
            'total_amount'    => $total_amount,
            'currency'        => $currency,
        ];

        $params = Liqpay::create_params(
            $user->full_name,
            $total_amount,
            'UAH',
            'Оплата заказа "' . $rate->order->name . '"',
            $info,
            'ru',
            $callback_url,
        );

        $payment = array_merge($params, $info);

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

        config(['logging.channels.daily.path' => storage_path('logs/payments/payment.log')]);
        Log::channel('daily')->info($liqpay);

        if (! in_array($liqpay['status'], ['success', 'sandbox'])) {
            throw new ErrorException('Статус платежа не равен "success" или "sandbox", получен статус "'.$liqpay['status'].'"');
        }

        $rate = Rate::whereKey($rate_id)->active()->first();
        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # ищем существующий чат или создаем новый
        $chat = Chat::searchOrCreate($rate->route_id, $rate->order_id, $rate->user_id, $liqpay['info']['user_id']);

        DB::beginTransaction();

        try {
            # обновляем данные по ставке
            $rate->status = Rate::STATUS_ACCEPTED;
            $rate->viewed_by_customer = true;
            $rate->chat_id = $chat->id;
            $rate->save();
            $rate->order()->update(['status' => Order::STATUS_IN_WORK]);

            Transaction::create([
                'user_id'         => $liqpay['info']['user_id'],
                'rate_id'         => $rate_id,
                'amount'          => $liqpay['amount'],
                'order_amount'    => $liqpay['info']['order_amount'],
                'payment_service_fee' => $liqpay['info']['liqpay_fee'],
                'delivery_amount' => $liqpay['info']['delivery_amount'],
                'company_fee'     => $liqpay['info']['service_fee'],
                'export_tax'      => $liqpay['info']['export_tax'],
                'description'     => $liqpay['description'],
                'status'          => $liqpay['status'],
                'complete_response' => $liqpay,
                'payed_at'        => gmdate('Y-m-d H:i:s', strtotime("+2 hours", $liqpay['end_date'] / 1000)),
            ]);

            # информируем в чат, что заказчик оплатил заказ.
            Chat::addSystemMessage($chat->id, 'customer_paid_order');

            # создаем уведомление "Ставка принята" для Путешественника
            if (active_notice_type($notice_type = NoticeType::RATE_ACCEPTED)) {
                Notice::create([
                    'user_id'     => $rate->user_id,
                    'notice_type' => $notice_type,
                    'object_id'   => $rate->order_id,
                    'data'        => ['order_name' => $rate->order->name, 'rate_id' => $rate->id],
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            Log::channel('daily')->debug($e->getMessage());

            throw new ErrorException($e->getMessage());
        }

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
        $rate = Rate::query()
            ->byKeyForRateOwner($rate_id, [Rate::STATUS_ACCEPTED])
            ->with(['order' => function ($query) {
                $query->withoutAppends()->select(['id', 'user_id', 'name']);
            }])
            ->first();
        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        $data = validateOrExit(['images' => 'required|array|max:5']);

        $rate->images = $data['images'];
        $rate->status = Rate::STATUS_BUYED;
        $rate->save();

        # отправляем системное сообщение, что исполнитель купил товар
        Chat::addSystemMessage($rate->chat_id, 'performer_buyed_product');

        # от имени исполнителя прикладываем фото к сообщению
        Message::create([
            'chat_id' => $rate->chat_id,
            'user_id' => $data['user_id'],
            'text' => '',
            'images' => $data['images'],
        ]);

        # создаем уведомление "Товар куплен Путешественником"
        if (active_notice_type($notice_type = NoticeType::PRODUCT_BUYED)) {
            Notice::create([
                'user_id'     => $rate->order->user_id,
                'notice_type' => $notice_type,
                'object_id'   => $rate->order->id,
                'data'        => ['order_name' => $rate->order->name, 'rate_id' => $rate->id],
            ]);
        }

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
        $rate = Rate::byKeyForOrderOwner($rate_id, [Rate::STATUS_BUYED], [Order::STATUS_IN_WORK])
            ->with('order')
            ->first();
        if (! $rate) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # меняем статусы на Ставке и Заказе
        $rate->status = Rate::STATUS_SUCCESSFUL;
        $rate->save();
        $rate->order()->update(['status' => Order::STATUS_SUCCESSFUL]);

        # подсчитываем сумму налогов по заказу
        $taxes_sum = OrderDeduction::sumTaxesByOrder($rate->order_id);

        # создаем заявку на возмещение средств по покупке и доставке заказа
        $payment = Payment::create([
            'user_id'     => $rate->user_id,
            'rate_id'     => $rate_id,
            'order_id'    => $rate->order_id,
            'amount'      => $rate->order->price_usd + $rate->order->user_price_usd + $taxes_sum,
            'type'        => Payment::TYPE_REWARD,
            'description' => 'Вознаграждение по заказу "' . $rate->order->name . '"',
        ]);

        # информируем в чат, что Заказчик получил товар.
        Chat::addSystemMessage($rate->chat_id, 'customer_received_order');

        return response()->json([
            'status'  => true,
            'rate'    => null_to_blank($rate),
            'payment' => null_to_blank($payment),
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

    /**
     * Ставки, которые не смотрел авторизированный пользователь, являющий владельцем заказа.
     *
     * @return JsonResponse
     */
    public function getRatesNotViewedByCustomer():JsonResponse
    {
        $rates = Rate::with('user:' . implode(',', User::FIELDS_FOR_SHOW))
            ->whereHas('order', function($query) {
                $query->owner();
            })
            ->notViewedByCustomer()
            ->get();

        return response()->json([
            'status'      => true,
            'rates'       => null_to_blank($rates),
            'rates_count' => count($rates),
        ]);
    }

    /**
     * Установка признака "Ставка просмотрена заказчиком".
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function setViewedByCustomer(Request $request):JsonResponse
    {
        $data = validateOrExit([
            'rate_id'   => 'required_without:order_id|array|min:1',
            'rate_id.*' => 'required|integer',
            'order_id'  => 'nullable|integer',
        ]);

        $affected_rows = Rate::query()
            ->where('viewed_by_customer', '=', 0)
            ->whereHas('order', function ($query) use ($data) {
                $query->where('user_id', $data['user_id']);
            })
            ->when($request->filled('rate_id'), function ($query) use ($data) {
                return $query->whereKey($data['rate_id']);
            })
            ->when($request->filled('order_id'), function ($query) use ($data) {
                return $query->where('order_id', $data['order_id']);
            })
            ->update(['viewed_by_customer' => 1]);

        return response()->json([
            'status'        => true,
            'affected_rows' => $affected_rows,
        ]);
    }
}
