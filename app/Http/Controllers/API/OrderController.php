<?php

namespace App\Http\Controllers\API;

use App\Events\OrderBanned;
use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Order;
use App\Models\Rate;
use App\Models\Route;
use App\Models\Shop;
use App\Models\User;
use App\Models\WaitRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /** @var int Количество заказов на странице */
    const DEFAULT_PER_PAGE = 5;

    /** @var array Поля для сортировки */
    const SORT_FIELDS = [
        'date'  => 'register_date',
        'price' => 'price_usd',
    ];

    /** @var string Дефолтная сортировка по дате добавления заказа */
    const DEFAULT_SORT_BY = 'date';

    /** @var string Дефолтная сортировка по убыванию */
    const DEFAULT_SORTING = 'desc';

    /** @var int Количество страйков, за которое выдается бан (заказ переводится в статус banned) */
    const COUNT_STRIKES_FOR_BAN = 50;

    # Cтраницы, на которых есть предустановленные фильтры: "Мои заказы" и "Заказы по маршруту"
    const PAGE_MY_ORDERS = 'my_orders';
    const PAGE_ORDERS_BY_ROUTE = 'orders_by_route';

    # Типы фильтров для отбора заказов на странице "Мои заказы": Ожидающие, В пути, Завершенные
    const FILTER_TYPE_WAITING = 'waiting';
    const FILTER_TYPE_ON_WAY = 'on_way';
    const FILTER_TYPE_COMPLETED = 'completed';

    # Типы фильтров для отбора заказов на странице "Заказы по маршруту": Заказы, Мои предложения, Принятые, Доставлено
    const FILTER_TYPE_ORDERS = 'orders';
    const FILTER_TYPE_MY_OFFERS = 'my_offers';
    const FILTER_TYPE_ACCEPTED = 'accepted';
    const FILTER_TYPE_DELIVERED = 'delivered';

    # Массив со всеми типами фильтров в разрезе страниц
    const FILTER_TYPES = [
        self::PAGE_MY_ORDERS => [
            self::FILTER_TYPE_WAITING,
            self::FILTER_TYPE_ON_WAY,
            self::FILTER_TYPE_COMPLETED,
        ],
        self::PAGE_ORDERS_BY_ROUTE => [
            self::FILTER_TYPE_ORDERS,
            self::FILTER_TYPE_MY_OFFERS,
            self::FILTER_TYPE_ACCEPTED,
            self::FILTER_TYPE_DELIVERED,
        ]
    ];

    /**
     * Правила проверки входных данных запроса при сохранении заказа.
     *
     * @return array
     */
    protected static function rules4saveOrder(): array
    {
        return [
            'name'           => 'required|string|censor|max:100',
            'product_link'   => 'sometimes|nullable|string|max:1000|url',
            'price'          => 'required|numeric',
            'currency'       => 'required|in:' . implode(',', config('app.currencies')),
            'products_count' => 'required|integer',
            'description'    => 'sometimes|nullable|string|not_phone|censor|max:5000',
            'from_country_id'=> 'required|integer|exists:countries,id',
            'from_city_id'   => 'sometimes|nullable|integer|exists:cities,id,country_id,' . request('from_country_id',  0),
            'to_country_id'  => 'required|integer|exists:countries,id',
            'to_city_id'     => 'sometimes|nullable|integer|exists:cities,id,country_id,' . request('to_country_id', 0),
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

        /*
        // в комменте https://app.asana.com/0/0/1203522478775625/1203546293769110/f решено вообще убрать проверку на дубль
        $exists_order = Order::active()->where(Arr::only($data, ['user_id', 'name', 'price', 'from_country_id', 'to_country_id']))->count();
        if ($exists_order) throw new ErrorException(__('message.order_exists'));
        */

        static::checkExistsImages($data['images']);

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
     * @throws ErrorException|ValidatorException|ValidationException
     */
    public function updateOrder(int $order_id, Request $request): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        if (! $order = Order::isOwnerByKey($order_id)->first()) {
            throw new ErrorException(__('message.order_not_found'));
        }

        $data = validateOrExit(self::rules4saveOrder());

        static::checkExistsImages($data['images']);

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
     * Заказы по выбранному маршруту.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function showOrdersByRoute(int $route_id, Request $request): JsonResponse
    {
        # проверяем входные данные
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

        # определяем тип фильтра
        $filter_type = $request->get('filter_type', self::FILTER_TYPE_ORDERS);
        if (! in_array($filter_type, self::FILTER_TYPES[self::PAGE_ORDERS_BY_ROUTE])) {
            $filter_type = self::FILTER_TYPE_ORDERS;
        }

        # получаем данные конкретного маршрута со связями
        $route = Route::getByIdWithRelations($route_id);

        # ругаемся, если нет маршрута
        if (! $route) {
            throw new ErrorException(__('message.route_not_found'));
        }

        # идентификатор аутентифицированного пользователя
        $auth_user_id = $request->user()->id;

        # если заказы просматривает владелец маршрута, то обновляем дату "Просмотра заказов"
        if ($filter_type == self::FILTER_TYPE_ORDERS && $route->user_id == $auth_user_id) {
            $route->viewed_orders_at = $route->freshTimestamp();
            $route->save();
        }

        # в разрезе всех типов фильтра подсчитываем кол-во заказов
        $counters = [];
        foreach (self::FILTER_TYPES[self::PAGE_ORDERS_BY_ROUTE] as $type) {
            $counters[$type] = self::prepareOrdersByRoute($route_id, $type, $filters)->count();
        }

        # получаем список заказов
        $orders = self::prepareOrdersByRoute($route_id, $filter_type, $filters)
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW)->withCount('successful_orders');
                },
                'from_country',
                'from_city',
                'to_country',
                'to_city',
                'rates',
                'rates.disputes',
                'deductions',
            ])
            ->withCount([
                'rates as rates_count',
                'rates as has_rate' => function ($query) use ($auth_user_id) {
                    $query->where('rates.user_id', $auth_user_id)
                        ->whereIn('rates.status', Rate::STATUSES_OK);
                },
                'rates as my_rate_id' => function($query) use ($auth_user_id) {
                    $query->where('rates.user_id', $auth_user_id)
                        ->whereIn('rates.status', Rate::STATUSES_OK)
                        ->select(DB::raw('MAX(id)'));
                },
                'deductions as deductions_sum' => function($query) {
                    $query->select(DB::raw('IFNULL(SUM(amount), 0)'));
                }
            ])
            ->orderBy(self::SORT_FIELDS[$filters['sort_by'] ?? self::DEFAULT_SORT_BY], $filters['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['orders.*'], 'page', $filters['page-number'] ?? 1);

        # если установлен фильтр "Принятые" или "Доставлено" и заказы просматривает владелец маршрута
        if (in_array($filter_type, [self::FILTER_TYPE_ACCEPTED, self::FILTER_TYPE_DELIVERED]) && $route->user_id == $request->user()->id) {
            foreach ($orders as $order) {
                foreach ($order->rates as $rate) {
                    # если ставка по заказу создана владельцем маршрута, то устанавливаем "Да" для "Подтвержденная ставка просмотрена исполнителем?"
                    if ($rate->user_id == $route->user_id) {
                        $rate->viewed_by_performer = true;
                        $rate->save();
                    }
                }
            }
        }

        $orders = $orders->toArray();

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
            $shop_slugs = array_filter($data->pluck('shop_slug')->unique()->all());
            $shops = $shop_slugs ? Shop::getBySlugs($shop_slugs) : [];
        }

        return response()->json([
            'status'   => true,
            'route'    => null_to_blank($route),
            'orders'   => null_to_blank($orders['data']),
            'counters' => $counters,
            'count'    => $orders['total'],
            'page'     => $orders['current_page'],
            'pages'    => $orders['last_page'],
            'prices'   => $prices,
            'shops'    => $shops,
        ]);
    }

    /**
     * Подготовить запрос для отбора списка заказов по выбранному маршруту, типу фильтра и дополнительным параметрам.
     *
     * @param int    $route_id     Код маршрута
     * @param string $filter_type  Тип фильтра (перечень см. в self::FILTER_TYPES)
     * @param array  $filters      Критерии отбора заказов
     * @return \Illuminate\Database\Eloquent\Builder|Order
     */
    protected static function prepareOrdersByRoute(int $route_id, string $filter_type = self::FILTER_TYPE_ORDERS, array $filters = [])
    {
        $orders = Order::query();

        # пункт "Заказы": заказы в статусе active, по которому подходит маршрут и нет ставки владельца маршрута
        if ($filter_type == self::FILTER_TYPE_ORDERS) {
            $orders->join('routes', 'routes.id', DB::raw($route_id))
                ->searchByRoutes(false, [Order::STATUS_ACTIVE])
                ->whereDoesntHave('rates', function ($query) use ($route_id) {
                    $query->whereRouteId($route_id);
                });

        # пункт "Мои предложения": заказы в статусе active и по заказу есть ставка владельца маршрута
        } elseif ($filter_type == self::FILTER_TYPE_MY_OFFERS) {
            $orders->active()
                ->whereHas('rates', function ($query) use ($route_id) {
                    $query->whereRouteId($route_id);
                });

        # пункт "Принятые": заказы, по которым есть ставка владельца маршрута со статусом accepted или buyed
        } elseif ($filter_type == self::FILTER_TYPE_ACCEPTED) {
            $orders->whereHas('rates', function ($query) use ($route_id) {
                $query->whereRouteId($route_id)->whereIn('status', [Rate::STATUS_ACCEPTED, Rate::STATUS_BUYED]);
            });

        # пункт "Доставлено": заказы, по которым есть ставка владельца маршрута со статусом successful или done
        } elseif ($filter_type == self::FILTER_TYPE_DELIVERED) {
            $orders = Order::whereHas('rates', function ($query) use ($route_id) {
                $query->whereRouteId($route_id)->whereIn('status', [Rate::STATUS_SUCCESSFUL, Rate::STATUS_DONE]);
            });
        }

        # дополнительные параметры фильтрации
        $orders->when(!empty($filters['price_from']), function ($query) use ($filters) {
            return $query->where('orders.price_usd', '>=', $filters['price_from']);
        })
        ->when(!empty($filters['price_to']), function ($query) use ($filters) {
            return $query->where('orders.price_usd', '<=', $filters['price_to']);
        })
        ->when(!empty($filters['shop']), function ($query) use ($filters) {
            return $query->whereIn('orders.shop_slug', $filters['shop']);
        });

        return $orders;
    }

    /**
     * Вывод выбранного заказа для редактирования владельцем.
     *
     * @param int $order_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function showOrderForEdit(int $order_id): JsonResponse
    {
        $order = Order::whereKey($order_id)
            ->owner()
            ->with([
                'from_country',
                'from_city',
                'to_country',
                'to_city',
                'wait_range',
            ])
            ->first();

        if (! $order) throw new ErrorException(__('message.order_not_found'));

        return response()->json([
            'status'                => true,
            'order'                 => null_to_blank($order),
            'countries_with_cities' => Country::getCountriesWithCities(),
            'wait_ranges'           => WaitRange::getWaitRanges(),
        ]);
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
        $auth_user_id = $request->user()->id;

        $order = Order::whereKey($order_id)
            ->withCount([
                'rates as rates_count',
                'rates as has_rate' => function ($query) use ($auth_user_id) {
                    $query->where('rates.user_id', $auth_user_id)
                        ->whereIn('rates.status', Rate::STATUSES_OK);
                },
                'rates as my_rate_id' => function($query) use ($auth_user_id) {
                    $query->where('rates.user_id', $auth_user_id)
                        ->whereIn('rates.status', Rate::STATUSES_OK)
                        ->select(DB::raw('MAX(id)'));
                },
                'deductions as deductions_sum' => function($query) {
                    $query->select(DB::raw('IFNULL(SUM(amount), 0)'));
                }
            ])
            ->with([
                'from_country',
                'from_city',
                'to_country',
                'to_city',
                'wait_range',
                'user',
                'deductions',
                'rates.user:' . implode(',', User::FIELDS_FOR_SHOW),
                'rate_confirmed.user:' . implode(',', User::FIELDS_FOR_SHOW),
                'rate_confirmed.dispute',
                'rate_confirmed.dispute.problem',
            ])
            ->first();

        if (! $order) throw new ErrorException(__('message.order_not_found'));

        return response()->json([
            'status' => true,
            'order'  => null_to_blank($order->toArray()),
        ]);
    }

    /**
     * Вывод моих заказов.
     *
     * @param Request $request
     * @throws ValidationException|ValidatorException*
     * @return JsonResponse
     */
    public function showMyOrders(Request $request): JsonResponse
    {
        $filters = validateOrExit([
            'status'      => 'nullable|in:' .  implode(',', self::FILTER_TYPES[self::PAGE_MY_ORDERS]),
            'show'        => 'nullable|integer|min:1',
            'page-number' => 'nullable|integer|min:1',
        ]);

        $status = $filters['status'] ?? self::FILTER_TYPE_WAITING;

        $orders = Order::owner()
            ->withCount([
                'deductions AS total_deductions_usd' => function($query) {
                    $query->select(DB::raw('IFNULL(SUM(amount), 0)'));
                },
            ])
            ->when($status == self::FILTER_TYPE_WAITING, function ($query) {
                $query->where('status', Order::STATUS_ACTIVE)
                    ->withCount([
                        'rates',
                        'rates as unread_rates_count' => function ($query) {
                            $query->where('viewed_by_customer', 0);
                        },
                    ])
                    ->with([
                        'rates' => function ($query) {
                            $query->withoutAppends()
                                ->select(['id', 'user_id', 'order_id', 'amount_usd', 'viewed_by_customer'])
                                ->latest('id');
                        },
                        'rates.user' => function ($query) {
                            $query->withoutAppends(['photo_thumb'])->select(['id', 'photo']);
                        },
                    ]);
            })
            ->when($status == self::FILTER_TYPE_ON_WAY, function ($query) {
                $query->where('status', Order::STATUS_IN_WORK)
                    ->with([
                        'rate_confirmed',
                        'rate_confirmed.user' => function ($query) {
                            $query->select(User::FIELDS_FOR_SHOW);
                        },
                    ]);
            })
            ->when($status == self::FILTER_TYPE_COMPLETED, function ($query) {
                $query->whereIN('status', [Order::STATUS_CLOSED, Order::STATUS_SUCCESSFUL, Order::STATUS_BANNED, Order::STATUS_FAILED])
                    ->with([
                        'rate_confirmed',
                        'rate_confirmed.user' => function ($query) {
                            $query->select(User::FIELDS_FOR_SHOW);
                        },
                    ]);
            })
            ->with([
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->orderBy(self::SORT_FIELDS[$filters['sort_by'] ?? self::DEFAULT_SORT_BY], $filters['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $filters['page-number'] ?? 1)
            ->toArray();

        if ($status == self::FILTER_TYPE_WAITING) {
            # по каждому заказу подсчитываем мин./макс. полную стоимость (общая сумма заказа + налоги и комиссии + цена ставки)
            foreach ($orders['data'] as &$order) {
                if ($order['rates_count']) {
                    # по всем ставкам формируем список вознаграждений в долларах
                    $rates_amount_usd = array_column($order['rates'], 'amount_usd');

                    # суммируем общую сумму заказа + налоги/комиссии + вознаграждение со ставки
                    $order['min_full_amount_usd'] = $order['total_amount_usd'] + $order['total_deductions_usd'] + min($rates_amount_usd);
                    $order['max_full_amount_usd'] = $order['total_amount_usd'] + $order['total_deductions_usd'] + max($rates_amount_usd);

                    # в массив заносим мин./макс. сумму в выбранной валюте пользователя
                    $order['min_full_amount_selected_currency'] = round($order['min_full_amount_usd'] * getCurrencyRate($order['selected_currency']));
                    $order['max_full_amount_selected_currency'] = round($order['max_full_amount_usd'] * getCurrencyRate($order['selected_currency']));
                } else {
                    $order['min_full_amount_usd'] = 0;
                    $order['max_full_amount_usd'] = 0;
                    $order['min_full_amount_selected_currency'] = 0;
                    $order['max_full_amount_selected_currency'] = 0;
                }
            }

        } elseif (in_array($status, [self::FILTER_TYPE_ON_WAY, self::FILTER_TYPE_COMPLETED])) {
            foreach ($orders['data'] as &$order) {
                if (!empty($order['rate_confirmed'])) {
                    $order['full_amount_usd'] = round($order['total_amount_usd'] + $order['total_deductions_usd'] + $order['rate_confirmed']['amount_usd'], 2);
                    $order['full_amount_selected_currency'] = round($order['full_amount_usd'] * getCurrencyRate($order['selected_currency']));
                } else {
                    $order['full_amount_usd'] = 0;
                    $order['full_amount_selected_currency'] = 0;
                }
            }
        }

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
            'owner_user_id'  => 'sometimes|required|integer',
            'status'         => 'sometimes|required|in:' .  implode(',', Order::STATUSES),
            'sorting'        => 'sometimes|required|in:asc,desc',
            'sort_by'        => 'sometimes|required|in:date,price',
            'show'           => 'sometimes|required|integer|min:1',
            'page-number'    => 'sometimes|required|integer|min:1',
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
            'city_from'      => 'sometimes|required|array',
            'city_from.*'    => 'nullable|integer',
            'city_to'        => 'sometimes|required|array',
            'city_to.*'      => 'nullable|integer',
            'country_from'   => 'sometimes|required|array',
            'country_from.*' => 'nullable|integer',
            'country_to'     => 'sometimes|required|array',
            'country_to.*'   => 'nullable|integer',
            'price_from'     => 'sometimes|required|numeric',
            'price_to'       => 'sometimes|required|numeric',
            'currency'       => 'sometimes|required|in:' . implode(',', config('app.currencies')),
        ]);

        $prices = DB::table('orders')
            ->selectRaw('MIN(price_usd) AS price_min, MAX(price_usd) AS price_max')
            ->first();

        $orders = $this->getOrdersByFilter($request->user(), $filters);

        $shops = [];
        if (!empty($orders['data'])) {
            $shop_slugs = array_filter(collect($orders['data'])->pluck('shop_slug')->unique()->all());
            if (! empty($shop_slugs)) {
                $shops = Shop::getBySlugs($shop_slugs);
            }
        }

        return response()->json([
            'status' => true,
            'count'  => $orders['total'],
            'page'   => $orders['current_page'],
            'pages'  => $orders['last_page'],
            'orders' => null_to_blank($orders['data']),
            'prices' => $prices,
            'shops'  => $shops,
        ]);
    }

    /**
     * Отбор заказов по фильтру.
     *
     * @param User|null $auth_user
     * @param array $filters
     * @return array
     */
    public function getOrdersByFilter(?User $auth_user, array $filters = []): array
    {
        $rate = !empty($filters['currency']) ? getCurrencyRate($filters['currency']) : 1;

        return Order::query()
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW)->withCount('successful_orders');
                },
                'from_country',
                'from_city',
                'to_country',
                'to_city',
                'rates' => function ($query) {
                    $query->latest('id');
                },
                'rates.user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW);
                },
                'rates.disputes',
                'deductions',
                'rate_confirmed',
                'rate_confirmed.user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW);
                },
            ])
            ->withCount('rates')
            ->withCount(['deductions AS total_deductions_usd' => function($query) {
                $query->select(DB::raw('IFNULL(SUM(amount), 0)'));
            }])
            ->withCount([
                'rates as has_rate' => function ($query) use ($auth_user) {
                    $query->where('rates.user_id', $auth_user->id ?? -1)
                        ->whereIn('rates.status', Rate::STATUSES_OK);
                },
                'rates as my_rate_id' => function($query) use ($auth_user) {
                    $query->where('rates.user_id', $auth_user->id ?? -1)
                        ->whereIn('rates.status', Rate::STATUSES_OK)
                        ->select(DB::raw('MAX(id)'));
                },
                'rates as read_rates_count' => function ($query){
                    $query->where('viewed_by_customer', 1);
                },
                'rates as unread_rates_count' => function ($query) {
                    $query->where('viewed_by_customer', 0);
                },
            ])
            ->when(!empty($filters['order_id']), function ($query) use ($filters) {
                return $query->where('orders.id', $filters['order_id']);
            })
            ->when(!empty($filters['without_order_id']), function ($query) use ($filters) {
                return $query->where('orders.id', '!=', $filters['without_order_id']);
            })
            ->when(!empty($filters['owner_user_id']), function ($query) use ($filters) {
                return $query->where('orders.user_id', $filters['owner_user_id']);
            })
            ->when(empty($filters['status']), function ($query) {
                return $query->whereNotIn('orders.status', [Order::STATUS_CLOSED, Order::STATUS_BANNED, Order::STATUS_SUCCESSFUL, Order::STATUS_FAILED]);
            }, function ($query) use ($filters) {
                return $query->where('orders.status', $filters['status']);
            })
            ->when(!empty($filters['date_from']), function ($query) use ($filters) {
                return $query->where('orders.register_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($query) use ($filters) {
                return $query->where('orders.deadline', '<=', $filters['date_to']);
            })
            ->when(!empty(array_filter($filters['city_from'] ?? [])), function ($query) use ($filters) {
                return $query->whereIn('orders.from_city_id', $filters['city_from']);
            })
            ->when(!empty(array_filter($filters['city_to'] ?? [])), function ($query) use ($filters) {
                return $query->whereIn('orders.to_city_id', $filters['city_to']);
            })
            ->when(!empty(array_filter($filters['country_from'] ?? [])), function ($query) use ($filters) {
                return $query->whereIn('orders.from_country_id', $filters['country_from']);
            })
            ->when(!empty(array_filter($filters['country_to'] ?? [])), function ($query) use ($filters) {
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
     * Для заказа подобрать маршруты.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function selectionRoutes(int $order_id, Request $request):JsonResponse
    {
        validateOrExit(['owner_user_id' => 'nullable|integer']);

        if (! $order = Order::find($order_id, ['from_country_id', 'to_country_id', 'register_date', 'deadline'])) {
            throw new ErrorException(__('message.order_not_found'));
        }

        $routes = Route::query()
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW);
                },
                'from_country',
                'to_country',
                'from_city',
                'to_city',
            ])
            ->where('status', Route::STATUS_ACTIVE)
            ->where('from_country_id', $order->from_country_id)
            ->where('to_country_id', $order->to_country_id)
            ->whereBetween('deadline', [$order->register_date, $order->deadline])
            ->when($request->filled('owner_user_id'), function ($query) use ($request) {
                return $query->where('user_id', $request->get('owner_user_id'));
            })
            ->get()
            ->toArray();

        return response()->json([
            'status' => true,
            'count'  => count($routes),
            'result' => null_to_blank($routes),
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
            $order->status = Order::STATUS_BANNED;

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

    /**
     * Проверить существование изображений по заказу.
     *
     * @param array $images
     * @throws ErrorException
     */
    private static function checkExistsImages(array $images)
    {
        foreach ($images as $image) {
            if (! remote_file_exists($image)) {
                throw new ErrorException(__('message.file_not_exists') . $image);
            };
        }
    }
}
