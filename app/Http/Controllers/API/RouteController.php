<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
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
            'from_country_id' => 'required|integer|exists:countries,id',
            'from_city_id'    => 'sometimes|exists_or_null:cities,id,country_id,' . request('from_country_id',  0),
            'to_country_id'   => 'required|integer|exists:countries,id',
            'to_city_id'      => 'sometimes|exists_or_null:cities,id,country_id,' . request('to_country_id', 0),
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

        $exists_route = Route::where($data)->count();
        if ($exists_route) throw new ErrorException(__('message.route_exists'));

        $route_id = Route::insertGetId($data);

        return response()->json([
            'status'   => true,
            'route_id' => $route_id,
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

        $affected = $route->update($data);

        return response()->json([
            'status'   => $affected,
            'route_id' => $route_id,
        ]);
    }

    /**
     * Закрыть маршрут(ы) (внутренний).
     *
     * @param mixed $id
     * @return JsonResponse
     */
    private static function closeRoute_int($id): JsonResponse
    {
        $affected_rows = Route::isOwnerByKey($id)->update(['status' => Order::STATUS_CLOSED]);

        return response()->json([
            'status'        => $affected_rows > 0,
            'affected_rows' => $affected_rows,
        ]);
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
            'status'      => 'sometimes|required|in:' .  implode(',', Route::STATUSES),
            'show'        => 'sometimes|required|integer|min:1',
            'page-number' => 'sometimes|required|integer|min:1',
        ]);

        $routes = $this->getMyRoutes($filters);

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
     *       WHERE orders.`status` = 'active'
     *         AND routes.deadline BETWEEN orders.register_date AND orders.deadline
     *         AND orders.from_country_id = routes.from_country_id
     *         AND orders.to_country_id = routes.to_country_id
     *         AND (orders.from_city_id = routes.from_city_id OR orders.from_city_id IS NULL AND routes.from_city_id > 0 OR routes.from_city_id IS NULL AND orders.from_city_id > 0)
     *         AND (orders.to_city_id = routes.to_city_id OR orders.to_city_id IS NULL AND routes.to_city_id > 0 OR routes.to_city_id IS NULL AND orders.to_city_id > 0)
     *     ) AS orders_cnt,
     *     (
     *       SELECT COUNT(1) FROM orders
     *       WHERE orders.`status` = 'active'
     *         AND routes.deadline BETWEEN orders.register_date AND orders.deadline
     *         AND orders.from_country_id = routes.from_country_id
     *         AND orders.to_country_id = routes.to_country_id
     *         AND (orders.from_city_id = routes.from_city_id OR orders.from_city_id IS NULL AND routes.from_city_id > 0 OR routes.from_city_id IS NULL AND orders.from_city_id > 0)
     *         AND (orders.to_city_id = routes.to_city_id OR orders.to_city_id IS NULL AND routes.to_city_id > 0 OR routes.to_city_id IS NULL AND orders.to_city_id > 0)
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
     *         AND `is_read` = 0
     *     ) AS rates_new_count,
     * 	   (
     *       SELECT IFNULL(SUM(orders.price_usd), 0) FROM orders
     *       JOIN rates ON rates.order_id = orders.id
     *       WHERE routes.id = rates.route_id
     *     ) AS budget_usd,
     *     (
     *       SELECT IFNULL(SUM(orders.user_price_usd), 0) FROM orders
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
        $status = $filters['status'] ?? Route::STATUS_ALL;

        # подзапросы для подсчета "Всего заказов" ($orders_all_count) и "Из них новых заказов" ($orders_new_count)
        if ($status == Route::STATUS_CLOSED) {
            # заглушка: для закрытых маршрутов всегда возвращаем 0
            $orders_all_count = $orders_new_count = DB::query()->selectRaw('0');
        } else {
            # количество всех заказов
            $orders_all_count = Order::selectRaw('count(1)')->searchByRoutes()->getQuery();

            # количество новых заказов
            $orders_new_count = Order::selectRaw('count(1)')->searchByRoutes(true)->getQuery();
        }

        return Route::owner()
            ->filterByStatus($status)
            ->with([
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount(['rates as rates_all_count' => function ($query) {
                $query->сonfirmed();
            }])
            ->withCount(['rates as rates_new_count' => function ($query) {
                $query->сonfirmed()->notRead();
            }])
            ->withCount(['order as budget_usd' => function($query) {
                $query->select(DB::raw('IFNULL(SUM(orders.price_usd), 0)'));
            }])
            ->withCount(['order as profit_usd' => function($query) {
                $query->select(DB::raw('IFNULL(SUM(orders.user_price_usd), 0)'));
            }])
            ->selectSub($orders_all_count, 'orders_all_count')
            ->selectSub($orders_new_count, 'orders_new_count')
            ->orderBy('id', 'desc')
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $filters['page-number'] ?? 1)
            ->toArray();
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
            'user_id'           => 'sometimes|required|integer',
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
                $query->where('is_read', 0)
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
            ->when(!empty($filters['user_id']), function ($query) use ($filters) {
                return $query->where('user_id', $filters['user_id']);
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
     * Подобрать заказ для маршрута.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function selectionOrder(int $route_id, Request $request):JsonResponse
    {
        if (!$route = Route::find($route_id)) {
            throw new ErrorException(__('message.route_not_found'));
        }

        $orders = Order::query()
            ->where('user_id',  $request->user()->id)
            ->where('status', Order::STATUS_ACTIVE)
            ->where('from_country', $route->route_from_country)
            ->where('fromdate', '>=', $route->fromdate)
            ->where('tilldate', '<=', $route->tilldate)
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
