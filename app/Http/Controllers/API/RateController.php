<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Dispute;
use App\Models\Message;
use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Order;
use App\Models\OrderDeduction;
use App\Models\Payment;
use App\Models\Rate;
use App\Models\Transaction;
use App\Models\User;
use App\Payments\Stripe;
use Carbon\Carbon;
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
            'route_id'   => 'required|integer|exists:routes,id,status,active,user_id,'.request()->user()->id,
            'order_id'   => 'required|integer|exists:orders,id,status,active',
            'deadline'   => 'required|date_format:Y-m-d|after_or_equal:'.date('Y-m-d'),
            'amount_usd' => 'required|numeric|min:10',
            'comment'    => 'nullable|string|censor|max:1000',
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
        if ($order->not_more_price && $data['amount_usd'] > $order->user_price_usd) {
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
        if ($order->not_more_price && $data['amount_usd'] > $order->user_price_usd) {
            throw new ErrorException(__('message.rate_exists_limit_user_price'));
        }

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
        if (! $rate = Rate::byKeyForRateOwner($rate_id)->first()) {
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
        $rate = Rate::byKeyForRateOwner($rate_id, [Rate::STATUS_ACTIVE, Rate::STATUS_CANCELED])->first();
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
     * Оплатить через Stripe по выбранной ставке.
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     */
    public function purchase(int $rate_id, Request $request): JsonResponse
    {
        # ищем активную ставку, где владельцем активного заказа является авторизированный пользователь
        /** @var Rate $rate */
        $rate = Rate::byKeyForOrderOwner($rate_id, [Rate::STATUS_ACTIVE], [Order::STATUS_ACTIVE])
            ->with('order')
            ->first();

        if (empty($rate)) {
            throw new ValidatorException(__('message.rate_not_found'));
        }

        if (empty($rate->order->stripe_product_id)) {
            throw new ValidatorException('Field stripe_product_id empty.');
        }

        /* TODO Расчет суммы для оплаты: Нужно реализовать конвертацию цены и дохода; добавить пошлину за ввоз; суммировать все расчеты */
        $order_amount = $rate->order->price_usd * $rate->order->products_count;
        $delivery_amount = $rate->amount_usd;
        $company_fee = round($order_amount * config('company_fee_percent') / 100, 2);
        $export_tax = 0;
        $total_amount = $order_amount + $delivery_amount + $export_tax + $company_fee;
        $payment_service_fee = round($total_amount * 2.9 / 100, 2) + 0.30;

        $user = $request->user();

        $stripe = new Stripe;
        if (empty($rate->stripe_price_id)) {
            $price = $stripe->createPrice($rate->order->stripe_product_id, $total_amount * 100, $rate->id);
            if (!empty($price['error'])) {
                throw new ValidatorException($price['error']);
            }
            $price_id = $price->id;
            $rate->stripe_price_id = $price_id;
            $rate->save();
        } else {
//            $price = $stripe->updatePrice($rate->stripe_price_id, $total_amount * 100);
//            if (!empty($price['error'])) {
//                throw new ValidatorException($price['error']);
//            }
            $price_id = $rate->stripe_price_id;
        }

        $transaction = Transaction::whereRateId($rate->id)->whereStatus('created')->first(['id', 'purchase_redirect_url']);
        if (!empty($transaction)) {
            return response()->json([
                'status' => true,
                'url'    => $transaction->purchase_redirect_url,
            ]);
        }

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

        $uri = $request->get('callback_url', config('app.wordpress_url'));
        $callback_url = preg_replace('/&status=.*/', '', $uri);

        $return_params = [
            'transaction_id' => $transaction->id,
            'callback_url'   => $callback_url,
        ];
        $purchase_params = [
            'customer_id' => $user->stripe_customer_id,
            'price_id' => $price_id,
            'transaction_id' => $transaction->id,
            'purchase_success_url' => route('purchase_success', $return_params),
            'purchase_error_url' => route('purchase_error', $return_params),
        ];
        $transaction->purchase_params = $purchase_params;
        $transaction->save();

        $checkout_session = $stripe->createCheckout($purchase_params);
        if (!empty($checkout_session['error'])) {
            $transaction->complete_error = $checkout_session['error'];
            $transaction->status = 'failed';
            $transaction->save();

            throw new ValidatorException($checkout_session['error']);
        }
        $transaction->purchase_response = $checkout_session->toJSON();
        $transaction->purchase_redirect_url = $checkout_session->url;
        $transaction->status = 'created';
        $transaction->save();

        return response()->json([
            'status' => true,
            'url'    => $checkout_session->url,
        ]);
    }

    /**
     * Клиент успешно оплатил в Stripe.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     */
    public function purchaseSuccess(Request $request)
    {
        if ($request->missing(['callback_url', 'transaction_id'])) {
            Log::channel('stripe')->error('[RateController.purchaseSuccess] Отсутствуют обязательные параметры: callback_url or transaction_id!');
            Log::channel('stripe')->debug('[RateController.purchaseSuccess] Параметры запроса:');
            Log::channel('stripe')->debug($request->all());

            return redirect(config('app.wordpress_url') . '?' . http_build_query([
                'status' => false,
                'error'  => '[purchaseSuccess] Отсутствуют обязательные параметры!',
            ]));
        }
        $callback_url = $request->get('callback_url');
        $transaction_id = $request->get('transaction_id');

        if (empty($transaction = Transaction::find($transaction_id))) {
            Log::channel('stripe')->error("[RateController.purchaseSuccess] Транзакция с кодом {$transaction_id} не найдена!");

            return redirect($callback_url . '&' . http_build_query([
                'status' => false,
                'error'  => "[purchaseSuccess] Транзакция с кодом {$transaction_id} не найдена!",
            ]));
        }

        # КЛИЕНТ УСПЕШНО ОПЛАТИЛ
//        $transaction->complete_response = $payment;
        $transaction->status = 'payed';
        $transaction->payed_at = Carbon::now()->toDateTimeString();
        $transaction->save();

        # ищем активную ставку
        $rate = Rate::whereKey($transaction->rate_id)->active()->first();
        if (! $rate) {
            return redirect($callback_url . '&' . http_build_query([
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
        $rate->order->update(['user_price_usd' => $rate->amount_usd,  'status' => Order::STATUS_IN_WORK]);

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

        return redirect($callback_url . '&status=true');
    }

    /**
     * Пользователь отменил платеж.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     */
    public function purchaseError(Request $request)
    {
        if ($request->missing(['callback_url', 'transaction_id'])) {
            Log::channel('stripe')->error('[RateController.purchaseError] Отсутствуют обязательные параметры: callback_url or transaction_id!');
            Log::channel('stripe')->debug('[RateController.purchaseError] Параметры запроса:');
            Log::channel('stripe')->debug($request->all());

            return redirect(config('app.wordpress_url') . '?' . http_build_query([
                'status' => false,
                'error'  => '[purchaseError] Отсутствуют обязательные параметры!',
            ]));
        }
        $callback_url = $request->get('callback_url');
        $transaction_id = $request->get('transaction_id');

        if (empty($transaction = Transaction::find($transaction_id))) {
            Log::channel('stripe')->error("[RateController.purchaseError] Транзакция с кодом {$transaction_id} не найдена!");

            return redirect($callback_url . '&' . http_build_query([
                'status' => false,
                'error'  => "[purchaseError] Транзакция с кодом {$transaction_id} не найдена!",
            ]));
        }

//        $transaction->complete_error = $request->all();
//        $transaction->status = 'canceled';
//        $transaction->save();

        return redirect($callback_url . '&' . http_build_query([
            'status'  => false,
            'error' => 'Пользователь отменил платеж.',
        ]));
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
     * - если был открыт спор, то он переводится в статус closed
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
        $rate->order->update(['status' => Order::STATUS_SUCCESSFUL]);

        # подсчитываем сумму налогов по заказу
        $taxes_sum = OrderDeduction::sumTaxesByOrder($rate->order_id);

        # создаем заявку на возмещение средств по покупке и доставке заказа
        $payment = Payment::create([
            'user_id'     => $rate->user_id,
            'rate_id'     => $rate_id,
            'order_id'    => $rate->order_id,
            'amount'      => $rate->order->total_amount_usd + $rate->amount_usd + $taxes_sum,
            'type'        => Payment::TYPE_REWARD,
            'description' => 'Вознаграждение по заказу "' . $rate->order->name . '"',
        ]);

        # закрываем действующий спор
        if ($dispute = Dispute::whereRateId($rate_id)->acting()->first()) {
            $dispute->update(['status' => Dispute::STATUS_CANCELED]);

            # информируем в чат об отмене спора
            Chat::addSystemMessage($dispute->chat_id, 'dispute_canceled');
        }

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
