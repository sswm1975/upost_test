<?php

namespace App\Http\Controllers\API;

use App\Events\OrderBanned;
use App\Exceptions\ErrorException;
use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Chat;
use App\Models\Route;
use App\Models\Shop;
use App\Models\User;
use App\Models\CurrencyRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

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

    /** @var array Типы фильтров для отбора заказов (Все, Принятые, Мои предложения, Доставленные) */
    const FILTER_ALL = 'all';
    const FILTER_ACCEPTED = 'accepted';
    const FILTER_SUGGESTIONS = 'suggestions';
    const FILTER_DELIVERED = 'delivered';
    const FILTER_TYPES = [
        self::FILTER_ALL,
        self::FILTER_ACCEPTED,
        self::FILTER_SUGGESTIONS,
        self::FILTER_DELIVERED,
    ];

    /**
     * Правила проверки входных данных запроса при сохранении заказа.
     *
     * @return array
     */
    protected function rules4saveOrder(): array
    {
        return [
            'product_link'   => 'sometimes|nullable|string|url',
            'name'           => 'required|string|censor|max:100',
            'price'          => 'required|numeric',
            'currency'       => 'required|in:' . implode(',', config('app.currencies')),
            'products_count' => 'required|integer',
            'description'    => 'required|string|not_phone|censor|max:5000',
            'from_country_id'=> 'required|integer|exists:countries,id',
            'from_city_id'   => 'required|required|integer|exists:cities,id,country_id,' . request('from_country_id',  0),
            'to_country_id'  => 'required|integer|exists:countries,id',
            'to_city_id'     => 'required|required|integer|exists:cities,id,country_id,' . request('to_country_id', 0),
            'wait_range_id'  => 'required|integer|exists:wait_ranges,id,active,1',
            'user_price'     => 'required|numeric',
            'user_currency'  => 'required|in:' . implode(',', config('app.currencies')),
            'not_more_price' => 'required|boolean',
            'images'         => 'required|array|max:8',
        ];
    }

    /**
     * Добавить заказ.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidatorException|ValidationException
     */
    public function addOrder(Request $request): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        $data = validateOrExit(self::rules4saveOrder());

        $exists_order = Order::where(Arr::only($data, ['user_id', 'name', 'price', 'from_country_id', 'to_country_id']))->count();
        if ($exists_order) throw new ErrorException(__('message.order_exists'));

        $order = Order::create($data);

        return response()->json([
            'status'   => true,
            'order_id' => $order->id,
            'slug'     => $order->slug,
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

        if (! $order = Order::isOwnerByKey($order_id)->first()) {
            throw new ErrorException(__('message.order_not_found'));
        }

        $data = validateOrExit(self::rules4saveOrder());

        $affected = $order->update($data);

        return response()->json([
            'status'   => $affected,
            'order_id' => $order->id,
            'slug'     => $order->slug,
        ]);
    }

    /**
     * Закрыть заказ(ы) (внутренний).
     *
     * @param mixed $id
     * @return JsonResponse
     */
    private static function closeOrder_int($id): JsonResponse
    {
        $affected_rows = Order::isOwnerByKey($id)->update(['status' => Order::STATUS_CLOSED]);

        return response()->json([
            'status'        => $affected_rows > 0,
            'affected_rows' => $affected_rows,
        ]);
    }

    /**
     * Закрыть заказ.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     */
    public function closeOrder(int $order_id, Request $request): JsonResponse
    {
        return self::closeOrder_int($order_id);
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

        return self::closeOrder_int($data['order_id']);
    }

    /**
     * Заказы по маршруту.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function showOrdersByRoute(int $route_id, Request $request): JsonResponse
    {
        $filter_type = $request->get('filter_type', self::FILTER_ALL);
        if (!array_key_exists($request->get('filter_type'), self::FILTER_TYPES)) {
            $filter_type = self::FILTER_ALL;
        }

        $filters = validateOrExit([
            'price_to'    => 'sometimes|required|numeric',
            'price_from'  => 'sometimes|required|numeric',
            'shop'        => 'sometimes|required|array',
            'shop.*'      => 'required',
            'sorting'     => 'sometimes|required|in:asc,desc',
            'sort_by'     => 'sometimes|required|in:date,price',
            'show'        => 'sometimes|required|integer|min:1',
            'page-number' => 'sometimes|required|integer|min:1',
        ]);

        $route = Route::getByIdWithRelations($route_id);

        if ($filter_type == self::FILTER_ALL) {
            $orders = $this->getAllOrdersByRoute($route_id, $filters);
        } else {
            $orders = [];
        }

        $prices = [
            'price_min' => 0,
            'price_max' => 0,
        ];
        $shops = [];

        if (!empty($orders['data'])) {
            $data = collect($orders['data']);
            $prices = [
                'price_min' => $data->min('price_usd'),
                'price_max' => $data->max('price_usd'),
            ];
            $shop_slugs = $data->pluck('shop_slug')->unique()->all();
            $shops = Shop::getBySlugs($shop_slugs);
        }

        return response()->json([
            'status' => true,
            'route'  => null_to_blank($route),
            'orders' => null_to_blank($orders['data']),
            'count'  => $orders['total'],
            'page'   => $orders['current_page'],
            'pages'  => $orders['last_page'],
            'prices' => $prices,
            'shops'  => $shops,
        ]);
    }

    /**
     * Получить список заказов по выбранному маршруту и фильтру "all-Заказы".
     *
     * @param int $route_id
     * @param array $filters
     * @return mixed
     */
    public function getAllOrdersByRoute(int $route_id, array $filters = [])
    {
        return Order::join('routes', 'routes.id', DB::raw($route_id))
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW)->withCount('successful_orders');
                },
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount([
                'rates as rates_count',
                'rates as has_rate' => function ($query) {
                    $query->whereColumn('rates.route_id', 'routes.id');
                },
            ])
            ->searchByRoutes()
            ->when(!empty($filters['price_from']), function ($query) use ($filters) {
                return $query->where('orders.price_usd', '>=', $filters['price_from']);
            })
            ->when(!empty($filters['price_to']), function ($query) use ($filters) {
                return $query->where('orders.price_usd', '<=', $filters['price_to']);
            })
            ->when(!empty($filters['shop']), function ($query) use ($filters) {
                return $query->whereIn('orders.shop_slug', $filters['shop']);
            })
            ->orderBy(self::SORT_FIELDS[$filters['sort_by'] ?? self::DEFAULT_SORT_BY], $filters['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $filters['page-number'] ?? 1)
            ->toArray();
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
                    'show' => 3,
                ]
            )['data'];
        }

        $last_orders = $this->getOrdersByFilter(
            $request->user(),
            [
                'without_order_id' => $order['id'],
                'user_id' => $order['user_id'],
                'show' => 3,
            ]
        )['data'];

        return response()->json([
            'status' => true,
            'order' => null_to_blank($order),
            'similar_orders' => null_to_blank($similar_orders),
            'more_orders' => null_to_blank($last_orders),
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
        $filters = array_merge(['user_id' => $user->id], $request->all());

        $orders = $this->getOrdersByFilter($user, $filters);

        return response()->json([
            'status' => true,
            'count'  => $orders['total'],
            'page'   => $orders['current_page'],
            'pages'  => $orders['last_page'],
            'orders' => null_to_blank($orders['data']),
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
            'status'         => 'sometimes|required|in:' .  implode(',', Order::STATUSES),
            'sorting'        => 'sometimes|required|in:asc,desc',
            'sort_by'        => 'sometimes|required|in:date,price',
            'show'           => 'sometimes|required|integer|min:1',
            'page-number'    => 'sometimes|required|integer|min:1',
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
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount([
                'rates as has_rate' => function ($query) use ($user) {
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
