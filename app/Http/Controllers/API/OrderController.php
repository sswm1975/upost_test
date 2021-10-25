<?php

namespace App\Http\Controllers\API;

use App\Events\OrderBanned;
use App\Exceptions\ErrorException;
use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Route;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Order;
use App\Models\CurrencyRate;

class OrderController extends Controller
{
    const DEFAULT_PER_PAGE = 5;
    const SORT_FIELDS = [
        'date'  => 'register_date',
        'price' => 'price_usd',
    ];
    const DEFAULT_SORT_BY = 'date';
    const DEFAULT_SORTING = 'desc';

    /** @var int Количество страйков, за которое выдается бан (заказ переводится в статус ban) */
    const COUNT_STRIKES_FOR_BAN = 50;

    /**
     * Добавить заказ.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     * @throws ValidatorException|ValidationException
     */
    public function addOrder(Request $request): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        $data = validateOrExit($this->validator($request->all()));

        $order = Order::create($data);

        return response()->json([
            'status'   => true,
            'order_id' => $order->id,
            'url'      => $order->slug,
        ]);
    }

    /**
     * Обновить заказ.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     * @throws ValidatorException|ValidationException
     */
    public function updateOrder(int $order_id, Request $request): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        # Ищем заказ по его коду - должен принадлежать авторизированному пользователю и быть активным.
        $order = Order::query()
            ->whereKey($order_id)
            ->where('user_id', $request->user()->id)
            ->where('status', Order::STATUS_ACTIVE)
            ->first();

        if (!$order) throw new ErrorException(__('message.order_not_found'));

        $data = validateOrExit($this->validator($request->all()));

        $order->update($data);

        return response()->json([
            'status'   => true,
            'order_id' => $order->id,
            'url'      => $order->slug,
        ]);
    }

    /**
     * Валидатор запроса с данными заказа.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data,
            [
                'product_link'   => 'sometimes|nullable|string|url',
                'name'           => 'required|string|censor|max:100',
                'category_id'    => 'required|integer|exists:categories,id',
                'price'          => 'required|numeric',
                'currency'       => 'required|in:' . implode(',', config('app.currencies')),
                'products_count' => 'required|integer',
                'size'           => 'required|string|max:50',
                'weight'         => 'required|string|max:50',
                'description'    => 'required|string|not_phone|censor|max:500',
                'from_country_id'=> 'required|integer|exists:countries,id',
                'from_city_id'   => 'sometimes|required|integer|exists:cities,id,country_id,' . ($data['from_country_id'] ?? 0),
                'from_address'   => 'sometimes|nullable|string',
                'to_country_id'  => 'required|integer|exists:countries,id',
                'to_city_id'     => 'sometimes|required|integer|exists:cities,id,country_id,' . ($data['to_country_id'] ?? 0),
                'to_address'     => 'sometimes|nullable|string',
                'fromdate'       => 'sometimes|required|date',
                'tilldate'       => 'required|date|after_or_equal:fromdate',
                'personal_price' => 'required|boolean',
                'user_price'     => 'required_if:personal_price,1',
                'user_currency'  => 'required_if:personal_price,1|sometimes|nullable|in:' . implode(',', config('app.currencies')),
                'not_more_price' => 'required|boolean',
                'images'         => 'required|array|max:8',
            ]
        );
    }

    /**
     * Удалить заказ.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function deleteOrder(int $order_id, Request $request): JsonResponse
    {
        $order = Order::query()
            ->where('id', $order_id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', [
                Order::STATUS_ACTIVE,
                Order::STATUS_BAN,
                Order::STATUS_CLOSED,
            ])
            ->first();

        if (!$order) throw new ErrorException(__('message.order_not_found'));

        $affected = $order->delete();

        return response()->json(['status' => (bool)$affected]);
    }

    /**
     * Вывод выбранного заказа.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function showOrder(int $order_id, Request $request): JsonResponse
    {
        $order = end($this->getOrdersByFilter($request->user(), compact('order_id'))['data']);

        if (!$order) throw new ErrorException(__('message.order_not_found'));

        $similar_orders = $this->getOrdersByFilter(
            $request->user(),
            [
                'without_order_id' => $order['id'],
                'city_from' => [$order['from_city_id']],
                'city_to' => [$order['to_city_id']],
                'show' => 3,
            ]
        )['data'];

        if (empty($similar_orders)) {
            $similar_orders = $this->getOrdersByFilter(
                $request->user(),
                [
                    'without_order_id' => $order['id'],
                    'category_id' => $order['category_id'],
                    'show' => 3,
                ]
            )['data'];
        }

        return response()->json([
            'status' => true,
            'order' => null_to_blank($order),
            'similar_orders' => null_to_blank($similar_orders),
        ]);
    }

    /**
     * Вывод моих заказов.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showMyOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = $this->getOrdersByFilter($user, ['user_id' => $user->id])['data'];

        return response()->json([
            'status' => true,
            'orders' => null_to_blank($orders),
        ]);
    }

    /**
     * Вывод заказов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function showOrders(Request $request): JsonResponse
    {
        $filters = validateOrExit([
            'order_id'       => 'sometimes|required|integer',
            'user_id'        => 'sometimes|required|integer',
            'status'         => 'sometimes|required|in:active,ban',
            'sorting'        => 'sometimes|required|in:asc,desc',
            'sort_by'        => 'sometimes|required|in:date,price',
            'show'           => 'sometimes|required|integer|min:1',
            'page-number'    => 'sometimes|required|integer|min:1',
            'category'       => 'sometimes|required|array',
            'category.*'     => 'required|integer',
            'date_from'      => 'sometimes|required|date',
            'date_to'        => 'sometimes|required|date|after_or_equal:date_from',
            'city_from'      => 'sometimes|required|array',
            'city_from.*'    => 'required|integer',
            'city_to'        => 'sometimes|required|array',
            'city_to.*'      => 'required|integer',
            'country_from'   => 'sometimes|required|array',
            'country_from.*' => 'required|integer',
            'country_to'     => 'sometimes|required|array',
            'country_to.*'   => 'required|integer',
            'price_from'     => 'sometimes|required|numeric',
            'price_to'       => 'sometimes|required|numeric',
            'currency'       => 'sometimes|required|in:' . implode(',', config('app.currencies')),
        ]);

        $orders = $this->getOrdersByFilter($request->user(), $filters);

        return response()->json([
            'status' => true,
            'count'  => $orders['total'],
            'page'   => $orders['current_page'],
            'pages'  => $orders['last_page'],
            'orders' => null_to_blank($orders['data']),
        ]);
    }

    /**
     * Отбор заказов по фильтру.
     *
     * @param User|null $user
     * @param array $filters
     * @return array
     */
    public function getOrdersByFilter(?User $user, array $filters = []): array
    {
        $rate = !empty($filters['currency']) ? CurrencyRate::getRate($filters['currency']) : 1;

        return Order::query()
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW)->withCount('successful_orders');
                },
                'category',
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount([
                'rates as have_rate' => function ($query) use ($user) {
                    $query->where('parent_id', 0)->where('user_id', $user->id ?? 0);
                },
                'rates as rates_read_count' => function ($query) use ($user) {
                    $query->where('is_read', 0)
                        ->when(!is_null($user), function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                },
                'rates as is_in_rate' => function ($query) use ($user) {
                    $query->typeOrder()
                        ->when(!is_null($user), function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                }
            ])
            ->when(!empty($filters['order_id']), function ($query) use ($filters) {
                return $query->where('orders.id', $filters['order_id']);
            })
            ->when(!empty($filters['without_order_id']), function ($query) use ($filters) {
                return $query->where('orders.id', '!=', $filters['without_order_id']);
            })
            ->when(!empty($filters['user_id']), function ($query) use ($filters) {
                return $query->where('orders.user_id', $filters['user_id']);
            })
            ->when(!empty($filters['category']), function ($query) use ($filters) {
                return $query->whereIn('orders.category_id', $filters['category']);
            })
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                return $query->where('orders.status', $filters['status']);
            })
            ->when(!empty($filters['date_from']), function ($query) use ($filters) {
                return $query->where('orders.fromdate', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($query) use ($filters) {
                return $query->where('orders.tilldate', '<=', $filters['date_to']);
            })
            ->when(!empty($filters['city_from']), function ($query) use ($filters) {
                return $query->whereIn('orders.from_city_id', $filters['city_from']);
            })
            ->when(!empty($filters['city_to']), function ($query) use ($filters) {
                return $query->whereIn('orders.to_city_id', $filters['city_to']);
            })
            ->when(!empty($filters['country_from']), function ($query) use ($filters) {
                return $query->whereIn('orders.from_country_id', $filters['country_from']);
            })
            ->when(!empty($filters['country_to']), function ($query) use ($filters) {
                return $query->whereIn('orders.to_country_id', $filters['country_to']);
            })
            ->when(!empty($filters['price_from']), function ($query) use ($filters, $rate) {
                return $query->where('orders.price_usd', '>=', $filters['price_from'] * $rate);
            })
            ->when(!empty($filters['price_to']), function ($query) use ($filters, $rate) {
                return $query->where('orders.price_usd', '<=', $filters['price_to'] * $rate);
            })
            ->orderBy(self::SORT_FIELDS[$filters['sort_by'] ?? self::DEFAULT_SORT_BY], $filters['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $filters['page-number'] ?? 1)
            ->toArray();
    }

    /**
     * Подобрать маршрут для заказа.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function selectionRoute(int $order_id, Request $request):JsonResponse
    {
        if (!$order = Order::find($order_id, ['from_country_id', 'fromdate', 'tilldate'])) {
            throw new ErrorException(__('message.order_not_found'));
        }

        $routes = Route::query()
            ->where('status', Route::STATUS_ACTIVE)
            ->where('from_country_id', $order->from_country_id)
            ->where('fromdate', '>=', $order->fromdate)
            ->where('tilldate', '<=', $order->tilldate)
            ->get()
            ->toArray();

        return response()->json([
            'status' => true,
            'count'  => count($routes),
            'result' => null_to_blank($routes),
        ]);
    }

    /**
     * Подтвердить выполнение заказа.
     *
     * @param Request $request
     * @return Request
     * @throws ValidatorException
     * @throws TryException
     */
    public function confirmOrder(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'chat_id'        => 'required|integer|exists:chats,chat_id',
            'user_id'        => 'required|integer|exists:users,id',
        ]);

        try {
            $this->confirmExtValidate($data);
        }
        catch (Exception $e) {
            throw new TryException($e->getMessage());
        }

        return response()->json([
            'status' => true
        ]);
    }

    /**
     * confirm order validation
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    private function confirmExtValidate(array $data): bool
    {
        $query = (new Chat())->newQuery();
        $count = $query->where('chat_id', $data['chat_id'])
            ->where('user_id', $data['user_id'])
            ->count();

        if($count == 0) {
            throw new Exception("This chat belongs to other user");
        }

        return true;
    }

    /**
     * Закрыть заказ.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function closeOrder(int $order_id, Request $request): JsonResponse
    {
        $order = Order::where([
            'id'      => $order_id,
            'user_id' => $request->user()->id,
            'status'  => Order::STATUS_ACTIVE,
        ])->first();

        if (!$order) {
            throw new ErrorException(__('message.order_not_found'));
        }

        $order->update(['status' => Order::STATUS_CLOSED]);

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Массовое закрытие заказов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function closeOrders(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'order_id'   => 'required|array|min:1',
            'order_id.*' => 'required|integer',
        ]);

        $affected_orders = Order::query()
            ->whereKey($data['order_id'])
            ->where([
                'user_id' => $request->user()->id,
                'status'  => Order::STATUS_ACTIVE,
            ])
            ->update(['status' => Order::STATUS_CLOSED]);

        return response()->json([
            'status'          => true,
            'affected_orders' => $affected_orders,
        ]);
    }

    /**
     * Пожаловаться на заказ.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function strikeOrder(int $order_id, Request $request): JsonResponse
    {
        validateOrExit([
            'strike_id' => 'required|integer',
        ]);

        if (!$order = Order::find($order_id)) {
            throw new ErrorException(__('message.order_not_found'));
        }

        $user_id = $request->user()->id;

        $strikes = $order->strikes;
        if ($strikes && array_key_exists($user_id, $strikes)) {
            throw new ErrorException(__('message.already_have_complaint'), 403);
        }
        $strikes[$user_id] = $request->get('strike_id');
        $order->strikes = $strikes;

        if (count($strikes) >= static::COUNT_STRIKES_FOR_BAN) {
            $order->status = Order::STATUS_BAN;

            event(new OrderBanned($order));
        }

        $order->save();

        return response()->json([
            'status' => true
        ]);
    }

    /**
     * Увеличить счётчик просмотров заказа.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function addLook(int $order_id, Request $request): JsonResponse
    {
        validateOrExit([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if (!$order = Order::find($order_id)) {
            throw new ErrorException(__('message.order_not_found'));
        }

        if ($order->user_id <> $request->get('user_id')) {
            $order->increment('order_look');
        }

        return response()->json([
            'status' => true,
            'looks'  => $order->order_look,
        ]);
    }
}
