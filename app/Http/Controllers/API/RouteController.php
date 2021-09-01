<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Order;
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
     * Вывод маршрутов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function showRoutes(Request $request): JsonResponse
    {
        $data = validateOrExit([
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

        $lang = app()->getLocale();

        $routes = Route::query()
            ->select(
                'routes.*',
                "from_country.country_name_{$lang} AS route_from_country_name",
                "to_country.country_name_{$lang} AS route_to_country_name",
                "from_city.city_name_{$lang} AS route_from_city_name",
                "to_city.city_name_{$lang} AS route_to_city_name",
                'users.user_name',
                DB::raw('IFNULL(LENGTH(users.user_favorite_routes) - LENGTH(REPLACE(users.user_favorite_routes, ",", "")) + 1, 0) AS cnt_favorite_routes')
            )
            ->join('users', 'users.user_id', 'routes.user_id')
            ->leftJoin('country AS from_country', 'from_country.country_id', 'routes.route_from_country')
            ->leftJoin('country AS to_country', 'to_country.country_id', 'routes.route_to_country')
            ->leftJoin('city AS from_city', 'from_city.city_id', 'routes.route_from_city')
            ->leftJoin('city AS to_city', 'to_city.city_id', 'routes.route_to_city')
            ->where('route_status', $request->get('status', 'active'))
            ->when($request->filled('route_id'), function ($query) use ($data) {
                return $query->whereIn('route_id', $data['route_id']);
            })
            ->when($request->filled('user_id'), function ($query) use ($data) {
                return $query->where('routes.user_id', $data['user_id']);
            })
            ->when($request->filled('date_from'), function ($query) use ($data) {
                return $query->where('routes.route_start', '>=', $data['date_from']);
            })
            ->when($request->filled('date_to'), function ($query) use ($data) {
                return $query->where('routes.route_end', '<=', $data['date_to']);
            })
            ->when($request->filled('country_from'), function ($query) use ($data) {
                return $query->whereIn('routes.route_from_country', $data['country_from']);
            })
            ->when($request->filled('city_from'), function ($query) use ($data) {
                return $query->whereIn('routes.route_from_city', $data['city_from']);
            })
            ->when($request->filled('country_to'), function ($query) use ($data) {
                return $query->whereIn('routes.route_to_country', $data['country_to']);
            })
            ->when($request->filled('city_to'), function ($query) use ($data) {
                return $query->whereIn('routes.route_to_city', $data['city_to']);
            })
            ->paginate($data['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $data['page'] ?? 1)
            ->toArray();

        return response()->json([
            'status' => true,
            'count'  => $routes['total'],
            'page'   => $routes['current_page'],
            'pages'  => $routes['last_page'],
            'result' => null_to_blank($routes['data']),
        ]);
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
