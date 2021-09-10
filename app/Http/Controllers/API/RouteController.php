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

        validateOrExit($this->validator($request->all()));

        $route = Route::create($request->all());

        return response()->json([
            'status' => true,
            'result' => null_to_blank($route->toArray()),
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
            'route_from_country' => 'required|integer|exists:country,country_id',
            'route_from_city'    => 'required_with:route_from_country|integer|exists:city,city_id,country_id,' . ($data['route_from_country'] ?? '0'),
            'route_to_country'   => 'required|integer|exists:country,country_id',
            'route_to_city'      => 'required_with:route_to_country|integer|exists:city,city_id,country_id,' . ($data['route_to_country'] ?? '0'),
            'route_start'        => 'required|date',
            'route_end'          => 'required|date|after_or_equal:route_start',
            'route_transport'    => 'required|in:car,bus,walk,train,plane',
            'route_points'       => 'sometimes|nullable|array',
        ];

        for($i = 0; $i < count($data['route_points'] ?? []); $i++) {
            $country_id = (int)($data['route_points'][$i]['country'] ?? 0);
            $rules["route_points.{$i}.country"] = 'sometimes|required|integer|exists:country,country_id';
            $rules["route_points.{$i}.city"]    = 'sometimes|required|integer|exists:city,city_id,country_id,' . $country_id;
            $rules["route_points.{$i}.date"]    = 'sometimes|required|date';
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
            ['route_id' => [$route_id]]
        )['data']);

        if (!$route) throw new ErrorException(__('message.route_not_found'));

        $similar_routes = $this->getRoutesByFilter(
            $request->user(),
            [
                'without_route_id' => $route['route_id'],
                'city_from' => [$route['route_from_city']],
                'city_to' => [$route['route_to_city']],
                'show' => 3,
            ]
        )['data'];

        return response()->json([
            'status' => true,
            'order' => null_to_blank($route),
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

        $routes = $this->getRoutesByFilter($user, ['user_id' => $user->user_id])['data'];

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
            'route_id'       => 'sometimes|required|array',
            'route_id.*'     => 'required|integer',
            'user_id'        => 'sometimes|required|integer',
            'status'         => 'sometimes|required|in:active,ban,close',
            'date_from'      => 'sometimes|required|date',
            'date_to'        => 'sometimes|required|date|after_or_equal:date_from',
            'country_from'   => 'sometimes|required|array',
            'country_from.*' => 'required|integer',
            'city_from'      => 'sometimes|required|array',
            'city_from.*'    => 'required|integer',
            'country_to'     => 'sometimes|required|array',
            'country_to.*'   => 'required|integer',
            'city_to'        => 'sometimes|required|array',
            'city_to.*'      => 'required|integer',
            'show'           => 'sometimes|required|integer|min:1',
            'page'           => 'sometimes|required|integer|min:1',
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
     * @param User $user
     * @param array $filters
     * @return array
     */
    private function getRoutesByFilter(User $user, array $filters = []): array
    {
        return Route::query()
            ->with([
                'user' => function ($query) {
                    $query->select([
                        'user_id',
                        'user_name',
                        'user_surname',
                        'user_creator_rating',
                        'user_freelancer_rating',
                        'user_photo',
                        'user_favorite_orders',
                        'user_favorite_routes',
                        DB::raw('(select count(*) from `orders` where `users`.`user_id` = `orders`.`user_id` and `order_status` = "successful") as user_successful_orders')
                    ]);
                },
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount(['rates as rates_all_count' => function ($query) use ($user) {
                $query->where('parent_id', 0)->where('user_id', $user->user_id);
            }])
            ->withCount(['rates as rates_read_count' => function ($query) use ($user) {
                $query->where('read_rate', 0)->where('user_id', $user->user_id);
            }])
            ->withCount(['rates as is_in_rate' => function ($query) use ($user) {
                $query->typeOrder()->where('user_id', $user->user_id);
            }])
            ->where('route_status', $filters['status'] ?? 'active')
            ->when(!empty($filters['route_id']), function ($query) use ($filters) {
                return $query->whereIn('routes.route_id', $filters['route_id']);
            })
            ->when(!empty($filters['without_route_id']), function ($query) use ($filters) {
                return $query->where('routes.route_id', '!=', $filters['without_route_id']);
            })
            ->when(!empty($filters['user_id']), function ($query) use ($filters) {
                return $query->where('routes.user_id', $filters['user_id']);
            })
            ->when(!empty($filters['date_from']), function ($query) use ($filters) {
                return $query->where('routes.route_start', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($query) use ($filters) {
                return $query->where('routes.route_end', '<=', $filters['date_to']);
            })
            ->when(!empty($filters['country_from']), function ($query) use ($filters) {
                return $query->whereIn('routes.route_from_country', $filters['country_from']);
            })
            ->when(!empty($filters['city_from']), function ($query) use ($filters) {
                return $query->whereIn('routes.route_from_city', $filters['city_from']);
            })
            ->when(!empty($filters['country_to']), function ($query) use ($filters) {
                return $query->whereIn('routes.route_to_country', $filters['country_to']);
            })
            ->when(!empty($filters['city_to']), function ($query) use ($filters) {
                return $query->whereIn('routes.route_to_city', $filters['city_to']);
            })
            ->orderBy('routes.route_id', 'desc')
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
            ->where('route_id', $route_id)
            ->where('user_id', $request->user()->user_id)
            ->where('route_status', 'active')
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
            ->where('route_id', $route_id)
            ->where('user_id',  $request->user()->user_id)
            ->whereIn('route_status', [
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
            ->where('user_id',  $request->user()->user_id)
            ->where('order_status', Order::STATUS_ACTIVE)
            ->where('order_from_country', $route->route_from_country)
            ->where('order_start', '>=', $route->route_start)
            ->where('order_deadline', '<=', $route->route_end)
            ->get()
            ->toArray();

        return response()->json([
            'status' => true,
            'count'  => count($orders),
            'result' => null_to_blank($orders),
        ]);
    }

    /**
     * Увеличить счетчик просмотров маршрута.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidatorException|ValidationException
     */
    public function addLook(int $route_id, Request $request): JsonResponse
    {
        validateOrExit([
            'user_id' => 'required|integer|exists:users,user_id',
        ]);

        if (!$route = Route::find($route_id)) {
            throw new ErrorException(__('message.route_not_found'));
        }

        if ($route->user_id <> $request->get('user_id')) {
            $route->increment('route_look');
        }

        return response()->json([
            'status'  => true,
            'looks'   => $route->route_look,
        ]);
    }
}
