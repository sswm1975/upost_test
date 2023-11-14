<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\OrderDeduction;
use App\Models\Rate;
use App\Models\Route;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    const DEFAULT_PER_PAGE = 5;

    /**
     * Правила проверки входных данных запроса при сохранении маршрута.
     *
     * @return array
     */
    protected static function rules4saveRoute(): array
    {
        return [
            'from_country_id' => 'required|string|size:2|exists:countries,id',
            'from_region'     => 'required_if:from_country_id,US',
            'from_city'       => 'sometimes|nullable|city_name',
            'to_country_id'   => 'required|string|size:2|exists:countries,id',
            'to_region'       => 'sometimes|nullable|string',
            'to_city'         => 'sometimes|nullable|city_name',
            'deadline'        => 'required|date|after_or_equal:'.date('Y-m-d'),
        ];
    }

    /**
     * Добавить маршрут.
     *
      * @return JsonResponse
     * @throws ErrorException|ValidatorException|ValidationException
     */
    public function addRoute(): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        $data = validateOrExit(self::rules4saveRoute());
        if (empty($data['from_region'])) $data['from_region'] = '';
        if (empty($data['to_region'])) $data['to_region'] = '';
        if (empty($data['to_region'])) $data['to_region'] = '';
        if (empty($data['from_city'])) $data['from_city'] = '';
        if (empty($data['to_city'])) $data['to_city'] = '';

        $data['from_city_id'] = City::getId($data['from_country_id'], $data['from_region'], $data['from_city']);
        $data['to_city_id'] = City::getId($data['to_country_id'], $data['to_region'], $data['to_city']);

        if (isEqualCountryAndCity($data['from_country_id'], $data['from_city_id'], $data['to_country_id'], $data['to_city_id'])) {
            throw new ErrorException(__('message.start_and_end_points_match'));
        }

        unset($data['from_city'], $data['to_city']);

        $exists_route = Route::where($data)->count();
        if ($exists_route) throw new ErrorException(__('message.route_exists'));

        $route = Route::create($data);

        return response()->json([
            'status'   => true,
            'route_id' => $route->id,
        ]);
    }

    /**
     * Редактировать маршрут.
     *
     * @param int route_id
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function updateRoute(int $route_id): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        if (! $route = Route::isOwnerByKey($route_id)->first()) {
            throw new ErrorException(__('message.route_not_found'));
        }

        $data = validateOrExit(self::rules4saveRoute());
        if (empty($data['from_region'])) $data['from_region'] = '';
        if (empty($data['to_region'])) $data['to_region'] = '';
        if (empty($data['to_region'])) $data['to_region'] = '';
        if (empty($data['from_city'])) $data['from_city'] = '';
        if (empty($data['to_city'])) $data['to_city'] = '';

        $data['from_city_id'] = City::getId($data['from_country_id'], $data['from_region'], $data['from_city']);
        $data['to_city_id'] = City::getId($data['to_country_id'], $data['to_region'], $data['to_city']);

        if (isEqualCountryAndCity($data['from_country_id'], $data['from_city_id'], $data['to_country_id'], $data['to_city_id'])) {
            throw new ErrorException(__('message.start_and_end_points_match'));
        }

        unset($data['from_city'], $data['to_city']);

        $affected = $route->update($data);

        return response()->json([
            'status'   => $affected,
            'route_id' => $route_id,
        ]);
    }

    /**
     * Закрыть маршрут(ы) (внутренний).
     * (закрыть маршрут может только владелец действующего маршрута, если все связанные ставки находятся в завершенном статусе)
     *
     * @param mixed $id
     * @return JsonResponse
     */
    private static function closeRoute_int($id): JsonResponse
    {
        Route::whereKey($id)
            ->active()
            ->owner()
            ->whereDoesntHave('rates', function($query) {
                return $query->whereNotIn('status', ['successful', 'done', 'failed', 'banned']);
            })
            ->get()
            ->each
            ->update(['status' => Order::STATUS_CLOSED]);

        return response()->json(['status' => true]);
    }

    /**
     * Закрыть маршрут.
     *
     * @param int $route_id
     * @return JsonResponse
     */
    public function closeRoute(int $route_id): JsonResponse
    {
        return self::closeRoute_int($route_id);
    }

    /**
     * Массовое закрытие маршрутов.
     *
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function closeRoutes(): JsonResponse
    {
        $data = validateOrExit([
            'route_id'   => 'required|array|min:1',
            'route_id.*' => 'required|integer',
        ]);

        return self::closeRoute_int($data['route_id']);
    }

    /**
     * Вывод выбранного маршрута.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function showRoute(int $route_id, Request $request): JsonResponse
    {
        $route = Route::getByIdWithRelations($route_id);

        if (!$route) throw new ErrorException(__('message.route_not_found'));

        return response()->json([
            'status' => true,
            'route'  => null_to_blank($route),
        ]);
    }

    /**
     * Вывод моих маршрутов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function showMyRoutes(Request $request): JsonResponse
    {
        $filters = validateOrExit([
            'status'      => 'sometimes|nullable|in:' .  implode(',', Route::FILTER_TYPES),
            'show'        => 'sometimes|nullable|integer|min:1',
            'page-number' => 'sometimes|nullable|integer|min:1',
        ]);

        $routes = $this->getMyRoutes($filters);

        # в разрезе всех типов фильтра (Активные, Завершенные) подсчитываем кол-во маршрутов
        $counters = [];
        foreach (Route::FILTER_TYPES as $type) {
            $counters[$type] = Route::owner()->filterByType($type)->count();
        }

        return response()->json([
            'status'   => true,
            'count'    => $routes['total'],
            'page'     => $routes['current_page'],
            'pages'    => $routes['last_page'],
            'routes'   => null_to_blank($routes['data']),
            'counters' => $counters,
        ]);
    }

    /**
     * Вывод маршрутов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function showRoutes(Request $request): JsonResponse
    {
        $filters = validateOrExit([
            'id'                => 'sometimes|required|array',
            'id.*'              => 'required|integer',
            'owner_user_id'     => 'sometimes|required|integer',
            'status'            => 'sometimes|required|in:' .  implode(',', Route::STATUSES),
            'date_from'         => 'sometimes|required|date',
            'date_to'           => 'sometimes|required|date|after_or_equal:date_from',
            'country_from'      => 'sometimes|required|array',
            'country_from.*'    => 'required|integer',
            'city_from'         => 'sometimes|required|array',
            'city_from.*'       => 'required|integer',
            'country_to'        => 'sometimes|required|array',
            'country_to.*'      => 'required|integer',
            'city_to'           => 'sometimes|required|array',
            'city_to.*'         => 'required|integer',
            'show'              => 'sometimes|required|integer|min:1',
            'page-number'       => 'sometimes|required|integer|min:1',
        ]);

        $routes = $this->getRoutesByFilter($request->user(), $filters);

        return response()->json([
            'status' => true,
            'count'  => $routes['total'],
            'page'   => $routes['current_page'],
            'pages'  => $routes['last_page'],
            'routes' => null_to_blank($routes['data']),
        ]);
    }

    /**
     * Получить мои маршруты.
     *
     * Сырой запрос, который формирует метод:
     *   SELECT
     *     routes.*,
     *     (
     *       SELECT COUNT(1) FROM orders
     *       WHERE orders.`status` IN ('active')
     *         AND routes.deadline BETWEEN orders.register_date AND orders.deadline
     *         AND orders.from_country_id = routes.from_country_id
     *         AND orders.to_country_id = routes.to_country_id
     *         AND (IFNULL(orders.from_city_id, 0) = IFNULL(routes.from_city_id, 0) OR orders.from_city_id IS NULL AND routes.from_city_id > 0 OR routes.from_city_id IS NULL AND orders.from_city_id > 0)
     *         AND (IFNULL(orders.to_city_id, 0) = IFNULL(routes.to_city_id, 0) OR orders.to_city_id IS NULL AND routes.to_city_id > 0 OR routes.to_city_id IS NULL AND orders.to_city_id > 0)
     *     ) AS orders_cnt,
     *     (
     *       SELECT COUNT(1) FROM orders
     *       WHERE orders.`status` IN ('active')
     *         AND routes.deadline BETWEEN orders.register_date AND orders.deadline
     *         AND orders.from_country_id = routes.from_country_id
     *         AND orders.to_country_id = routes.to_country_id
     *         AND (IFNULL(orders.from_city_id, 0) = IFNULL(routes.from_city_id, 0) OR orders.from_city_id IS NULL AND routes.from_city_id > 0 OR routes.from_city_id IS NULL AND orders.from_city_id > 0)
     *         AND (IFNULL(orders.to_city_id, 0) = IFNULL(routes.to_city_id, 0) OR orders.to_city_id IS NULL AND routes.to_city_id > 0 OR routes.to_city_id IS NULL AND orders.to_city_id > 0)
     *         AND orders.created_at > IFNULL(routes.viewed_orders_at, '1900-01-01 00:00:00')
     *     ) AS orders_new_cnt,
     *     (
     *       SELECT COUNT(1) FROM rates
     *       WHERE routes.id = rates.route_id
     *         AND `status` IN ('accepted', 'buyed', 'successful', 'done')
     *     ) AS rates_all_count,
     *     (
     *       SELECT COUNT(1) FROM rates
     *       WHERE routes.id = rates.route_id
     *         AND `status` IN ('accepted', 'buyed', 'successful', 'done')
     *         AND `viewed_by_performer` = 0
     *     ) AS rates_new_count,
     * 	   (
     *       SELECT IFNULL(SUM(orders.price_usd * orders.products_count + orders.deduction_usd), 0)
     *       FROM orders
     *       JOIN rates ON rates.order_id = orders.id
     *       WHERE routes.id = rates.route_id AND orders.status NOT IN ('active', 'banned', 'failed')
     *     ) AS budget_usd,
     *     (
     *       SELECT IFNULL(SUM(orders.user_price_usd), 0)
     *       FROM orders
     *       JOIN rates ON rates.order_id = orders.id
     *       WHERE routes.id = rates.route_id
     *     ) AS profit_usd
     *   FROM routes
     *   WHERE routes.user_id = 21
     *   AND routes.`status` IN ('active', 'in_work')
     *   ORDER BY `id` DESC
     *   LIMIT 5
     *   OFFSET 0
     *
     * @param array $filters
     * @return array
     */
    private function getMyRoutes(array $filters = []): array
    {
        $status = $filters['status'] ?? Route::FILTER_TYPE_ACTIVE;

        # подзапросы для подсчета "Всего заказов" ($orders_all_count) и "Из них новых заказов" ($orders_new_count)
        if ($status == Route::FILTER_TYPE_ACTIVE) {
            $orders_all_count = Order::selectRaw('count(1)')->searchByRoutes(false)->getQuery();
            $orders_new_count = Order::selectRaw('count(1)')->searchByRoutes(true)->getQuery();
        } else {
            $orders_all_count = Rate::selectRaw('COUNT(DISTINCT order_id)')->where('route_id', '=', 'routes.id')->getQuery();
            # заглушка: для завершенных "Кол-во новых" всегда возвращаем 0, получается такой подзапрос (select 0) as orders_new_count
            $orders_new_count = DB::query()->selectRaw('0');
        }

        return Route::owner()
            ->filterByType($status)
            ->with([
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount(['rates as rates_all_count' => function ($query) {
                $query->confirmed();
            }])
            ->withCount(['rates as rates_new_count' => function ($query) {
                $query->confirmed()->notViewedByPerformer();
            }])
            ->withCount(['order as budget_usd' => function($query) {
                $query
                    ->whereNotIn('orders.status', [Order::STATUS_ACTIVE, Order::STATUS_FAILED, Order::STATUS_BANNED])
                    ->select(DB::raw('IFNULL(SUM(orders.price_usd * orders.products_count + orders.deduction_usd), 0)'));
            }])
            ->withCount(['order as profit_usd' => function($query) {
                $query
                    ->whereNotIn('orders.status', [Order::STATUS_ACTIVE, Order::STATUS_FAILED, Order::STATUS_BANNED])
                    ->select(DB::raw('IFNULL(SUM(orders.user_price_usd), 0)'));
            }])
            ->selectSub($orders_all_count, 'orders_all_count')
            ->selectSub($orders_new_count, 'orders_new_count')
            ->orderBy('id', 'desc')
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $filters['page-number'] ?? 1)
            ->toArray();
    }

    /**
     * Отбор маршрутов по фильтру.
     *
     * @param User|null $user
     * @param array $filters
     * @return array
     */
    public function getRoutesByFilter(?User $user, array $filters = []): array
    {
        return Route::query()
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW)
                        ->addSelect(DB::raw('(select count(*) from `orders` where `users`.`id` = `orders`.`user_id` and `status` = "successful") as successful_orders'));
                },
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
/*
            ->withCount(['rates as has_rate' => function ($query) use ($user) {
                $query->where('user_id', $user->id ?? 0);
            }])
            ->withCount(['rates as rates_all_count' => function ($query) use ($user) {
                $query->when(!is_null($user), function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }])
            ->withCount(['rates as rates_read_count' => function ($query) use ($user) {
                $query->where('viewed_by_customer', 0)
                    ->when(!is_null($user), function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            }])
            ->withCount(['rates as is_in_rate' => function ($query) use ($user) {
                $query->when(!is_null($user), function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            }])
*/
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                return $query->where('status', $filters['status']);
            })
            ->when(!empty($filters['id']), function ($query) use ($filters) {
                return $query->whereIn('id', $filters['id']);
            })
            ->when(!empty($filters['without_id']), function ($query) use ($filters) {
                return $query->where('id', '!=', $filters['without_id']);
            })
            ->when(!empty($filters['owner_user_id']), function ($query) use ($filters) {
                return $query->where('user_id', $filters['owner_user_id']);
            })
            ->when(!empty($filters['date_from']), function ($query) use ($filters) {
                return $query->where('fromdate', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($query) use ($filters) {
                return $query->where('tilldate', '<=', $filters['date_to']);
            })
            ->when(!empty($filters['country_from']), function ($query) use ($filters) {
                return $query->existsCountryInFromCountry($filters['country_from']);
            })
            ->when(!empty($filters['city_from']), function ($query) use ($filters) {
                return $query->existsCityInFromCity($filters['city_from']);
            })
            ->when(!empty($filters['country_to']), function ($query) use ($filters) {
                return $query->existsCountryInToCountry($filters['country_to']);
            })
            ->when(!empty($filters['city_to']), function ($query) use ($filters) {
                return $query->existsCityInToCity($filters['city_to']);
            })
            ->orderBy('id', 'desc')
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $filters['page-number'] ?? 1)
            ->toArray();
    }


    /**
     * Подобрать заказы для маршрута.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function selectionOrders(int $route_id, Request $request):JsonResponse
    {
        if (!$route = Route::find($route_id)) {
            throw new ErrorException(__('message.route_not_found'));
        }

        $orders = Order::query()
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW);
                },
                'from_country',
                'from_city',
                'to_country',
                'to_city',
                'deductions',
            ])
            ->withCount(['deductions AS deductions_sum' => function($query) {
                $query->select(DB::raw('IFNULL(SUM(amount), 0)'));
            }])
            ->where('user_id',  $request->user()->id)
            ->where('status', Order::STATUS_ACTIVE)
            ->where('from_country_id', $route->from_country_id)
            ->where('to_country_id', $route->to_country_id)
            ->whereBetween('deadline', [$route->created_at, $route->deadline . ' 23:59:59'])
            ->get()
            ->toArray();

        return response()->json([
            'status' => true,
            'count'  => count($orders),
            'result' => null_to_blank($orders),
        ]);
    }

    /**
     * Увеличить счётчик просмотров маршрута.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidatorException|ValidationException
     */
    public function addLook(int $route_id, Request $request): JsonResponse
    {
        validateOrExit(['user_id' => 'required|integer|exists:users,id']);

        if (!$route = Route::find($route_id)) {
            throw new ErrorException(__('message.route_not_found'));
        }

        if ($route->user_id <> $request->get('user_id')) {
            $route->increment('looks');
        }

        return response()->json([
            'status'  => true,
            'looks'   => $route->looks,
        ]);
    }
}
