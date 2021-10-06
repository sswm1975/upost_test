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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    const DEFAULT_PER_PAGE = 5;

    /**
     * Добавить маршрут.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidatorException|ValidationException
     */
    public function addRoute(Request $request): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        $data = validateOrExit($this->validator($request->all()));

        $route = Route::create($data);

        if (isset($data['route_points'])) {
            $route->route_points()->createMany($data['route_points']);
        }

        $route = Route::with('route_points:route_id,country_id,city_id,date')
            ->find($route->id)
            ->toArray();

        return response()->json([
            'status' => true,
            'result' => null_to_blank($route),
        ]);
    }

    /**
     * Валидатор запроса с данными маршрута.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $rules = [
            'from_country_id' => 'required|integer|exists:countries,id',
            'from_city_id'    => 'required_with:from_country_id|integer|exists:cities,id,country_id,' . ($data['from_country_id'] ?? '0'),
            'to_country_id'   => 'required|integer|exists:countries,id',
            'to_city_id'      => 'required_with:to_country|integer|exists:cities,id,country_id,' . ($data['to_country_id'] ?? '0'),
            'fromdate'        => 'required|date',
            'tilldate'        => 'required|date|after_or_equal:fromdate',
            'transport'       => 'required|in:car,bus,walk,train,plane',
            'route_points'    => 'sometimes|nullable|array',
        ];

        for ($i = 0; $i < count($data['route_points'] ?? []); $i++) {
            $country_id = (int)($data['route_points'][$i]['country_id'] ?? 0);
            $rules["route_points.{$i}.country_id"] = 'sometimes|required|integer|exists:countries,id';
            $rules["route_points.{$i}.city_id"]    = 'sometimes|required|integer|exists:cities,id,country_id,' . $country_id;
            $rules["route_points.{$i}.date"]       = 'sometimes|required|date';
        }

        return Validator::make($data, $rules);
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

        $routes = $this->getRoutesByFilter($user, ['user_id' => $user->id])['data'];

        return response()->json([
            'status' => true,
            'routes' => null_to_blank($routes),
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
            'status'            => 'sometimes|required|in:active,ban,close',
            'fromdate'          => 'sometimes|required|date',
            'tilldate'          => 'sometimes|required|date|after_or_equal:fromdate',
            'from_country_id'   => 'sometimes|required|array',
            'from_country_id.*' => 'required|integer',
            'from_city_id'      => 'sometimes|required|array',
            'from_city_id.*'    => 'required|integer',
            'to_country_id'     => 'sometimes|required|array',
            'to_country_id.*'   => 'required|integer',
            'to_city_id'        => 'sometimes|required|array',
            'to_city_id.*'      => 'required|integer',
            'show'              => 'sometimes|required|integer|min:1',
            'page'              => 'sometimes|required|integer|min:1',
        ]);

        $routes = $this->getRoutesByFilter($request->user(), $filters);

        return response()->json([
            'status' => true,
            'count'  => $routes['total'],
            'page'   => $routes['current_page'],
            'pages'  => $routes['last_page'],
            'result' => null_to_blank($routes['data']),
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
                'route_points.country',
                'route_points.city'
            ])
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
            ->where('status', $filters['status'] ?? 'active')
            ->when(!empty($filters['id']), function ($query) use ($filters) {
                return $query->whereIn('id', $filters['id']);
            })
            ->when(!empty($filters['without_id']), function ($query) use ($filters) {
                return $query->where('id', '!=', $filters['without_id']);
            })
            ->when(!empty($filters['user_id']), function ($query) use ($filters) {
                return $query->where('user_id', $filters['user_id']);
            })
            ->when(!empty($filters['fromdate']), function ($query) use ($filters) {
                return $query->where('fromdate', '>=', $filters['fromdate']);
            })
            ->when(!empty($filters['tilldate']), function ($query) use ($filters) {
                return $query->where('tilldate', '<=', $filters['tilldate']);
            })
            ->when(!empty($filters['from_country_id']), function ($query) use ($filters) {
                return $query->existsCountryInFromCountry($filters['from_country_id']);
            })
            ->when(!empty($filters['from_city_id']), function ($query) use ($filters) {
                return $query->existsCityInFromCity($filters['from_city_id']);
            })
            ->when(!empty($filters['to_country_id']), function ($query) use ($filters) {
                return $query->existsCountryInToCountry($filters['to_country_id']);
            })
            ->when(!empty($filters['to_city_id']), function ($query) use ($filters) {
                return $query->existsCityInToCity($filters['to_city_id']);
            })
            ->orderBy('id', 'desc')
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $filters['page'] ?? 1)
            ->toArray();
    }

    /**
     * Редактирование маршрута.
     *
     * @param int route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ErrorException|ValidatorException
     */
    public function updateRoute(int $route_id, Request $request): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        # Ищем маршрут по его коду, он должен принадлежать авторизированному пользователю и быть активным
        $route = Route::query()
            ->where('id', $route_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->first();

        if (!$route) throw new ErrorException(__('message.route_not_found'));

        $data = validateOrExit($this->validator($request->all()));

        $route->update($data);

        return response()->json([
            'status'  => true,
            'result'  => null_to_blank($route->toArray()),
        ]);
    }

    /**
     * Удалить маршрут.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function deleteRoute(int $route_id, Request $request): JsonResponse
    {
        # Ищем маршрут по его коду, он должен принадлежать авторизированному пользователю и быть в одном из разрешенных статусов
        $route = Route::query()
            ->where('id', $route_id)
            ->where('user_id',  $request->user()->id)
            ->whereIn('status', [
                Route::STATUS_ACTIVE,
                Route::STATUS_BAN,
                Route::STATUS_CLOSED,
            ])
            ->first();

        if (!$route) throw new ErrorException(__('message.route_not_found'));

        $affected = $route->delete();

        return response()->json(['status' => (bool)$affected]);
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
