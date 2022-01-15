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
            'status' => $affected,
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
     * Удалить заказ(ы) (внутренний).
     *
     * @param mixed $id
     * @return JsonResponse
     */
    private static function deleteRoute_int($id): JsonResponse
    {
        $affected_rows = Route::isOwnerByKey($id)->delete();

        return response()->json([
            'status'        => $affected_rows > 0,
            'affected_rows' => $affected_rows,
        ]);
    }

    /**
     * Удалить маршрут.
     * (разрешено удалять только в определенных статусах)
     *
     * @param int $route_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function deleteRoute(int $route_id): JsonResponse
    {
        return self::deleteRoute_int($route_id);
    }

    /**
     * Массовое удаление маршрутов.
     *
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function deleteRoutes(): JsonResponse
    {
        $data = validateOrExit([
            'route_id'   => 'required|array|min:1',
            'route_id.*' => 'required|integer',
        ]);

        return self::deleteRoute_int($data['route_id']);
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
        $route = end($this->getRoutesByFilter(
            $request->user(),
            ['id' => [$route_id]]
        )['data']);

        if (!$route) throw new ErrorException(__('message.route_not_found'));

        $similar_routes = $this->getRoutesByFilter(
            $request->user(),
            [
                'without_route_id' => $route['id'],
                'city_from' => [$route['from_city_id']],
                'city_to' => [$route['to_city_id']],
                'show' => 3,
            ]
        )['data'];

        return response()->json([
            'status' => true,
            'route' => null_to_blank($route),
            'similar_routes' => null_to_blank($similar_routes),
        ]);
    }

    /**
     * Вывод моих маршрутов.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showMyRoutes(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = array_merge(['user_id' => $user->id], $request->all());

        $routes = $this->getRoutesByFilter($user, $filters);

        return response()->json([
            'status' => true,
            'count'  => $routes['total'],
            'page'   => $routes['current_page'],
            'pages'  => $routes['last_page'],
            'routes' => null_to_blank($routes['data']),
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
            ->withCount(['rates as has_rate' => function ($query) use ($user) {
                $query->where('parent_id', 0)->where('user_id', $user->id ?? 0);
            }])
            ->withCount(['rates as rates_all_count' => function ($query) use ($user) {
                $query->where('parent_id', 0)
                    ->when(!is_null($user), function ($q) use ($user) {
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
                $query->typeOrder()
                    ->when(!is_null($user), function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            }])
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
