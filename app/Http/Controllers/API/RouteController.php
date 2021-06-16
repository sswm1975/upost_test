<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    const DEFAULT_PER_PAGE = 5;

    /**
     * Сохранить маршрут.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveRoute(Request $request): JsonResponse
    {
        $user = $request->user();

        # Якшо ім'я, прізвище, дата народження не заповнені - то не давати створити маршрут.
        if (empty($user->user_name) || empty($user->user_surname) || empty($user->user_birthday)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.not_filled_profile')],
            ], 404);
        }
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 404);
        }

        $request->merge(['user_id' => $user->user_id]);

        $route = Route::create($request->all());

        return response()->json([
            'status'  => true,
            'result'  => null_to_blank($route->toArray()),
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
     * @throws ValidationException
     */
    public function showRoutes(Request $request): JsonResponse
    {
        $validator = Validator::make(request()->all(),
            [
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
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all()
            ], 404);
        }

        $data = $validator->validated();

        $routes = Route::query()
            ->where('route_status', $request->get('status', 'active'))
            ->when($request->filled('route_id'), function ($query) use ($data) {
                return $query->whereIn('route_id', $data['route_id']);
            })
            ->when($request->filled('user_id'), function ($query) use ($data) {
                return $query->where('user_id', $data['user_id']);
            })
            ->when($request->filled('date_from'), function ($query) use ($data) {
                return $query->where('route_start', '>=', $data['date_from']);
            })
            ->when($request->filled('date_to'), function ($query) use ($data) {
                return $query->where('route_end', '<=', $data['date_to']);
            })
            ->when($request->filled('country_from'), function ($query) use ($data) {
                return $query->whereIn('route_from_country', $data['country_from']);
            })
            ->when($request->filled('city_from'), function ($query) use ($data) {
                return $query->whereIn('route_from_city', $data['city_from']);
            })
            ->when($request->filled('country_to'), function ($query) use ($data) {
                return $query->whereIn('route_to_country', $data['country_to']);
            })
            ->when($request->filled('city_to'), function ($query) use ($data) {
                return $query->whereIn('route_to_city', $data['city_to']);
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
     * @throws ValidationException
     */
    public function updateRoute(int $route_id, Request $request): JsonResponse
    {
        $user = $request->user();

        # Якшо ім'я, прізвище, дата народження не заповнені - то не давати створити маршрут.
        if (empty($user->user_name) || empty($user->user_surname) || empty($user->user_birthday)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.not_filled_profile')],
            ], 404);
        }

        # Ищем маршрут по его коду, он должен принадлежать авторизированному пользователю и быть активным
        $route = Route::query()
            ->where('route_id', $route_id)
            ->where('user_id', $user->user_id)
            ->where('route_status', 'active')
            ->first();

        if (empty($route)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.route_not_found')],
            ], 404);
        }

        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all()
            ], 404);
        }

        $route->update($validator->validated());

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
     */
    public function deleteRoute(int $route_id, Request $request): JsonResponse
    {
        # Ищем маршрут по его коду, он должен принадлежать авторизированному пользователю и быть в одном из разрешенных статусов
        $route = Route::query()
            ->where('route_id', $route_id)
            ->where('user_id',  $request->user()->user_id)
            ->whereIn('route_status', ['active', 'ban', 'close'])
            ->first();

        if (empty($route)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.route_not_found')],
            ], 404);
        }

        $affected = $route->delete();

        return response()->json(['status' => (bool)$affected]);
    }

    /**
     * Подобрать маршрут.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     */
    public function selectionRoute(int $order_id, Request $request):JsonResponse
    {
        $user = $request->user();

        $order = Order::find($order_id);

        if (empty($order)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.order_not_found')],
            ], 404);
        }

        $routes = Route::query()
            ->where('user_id', $user->user_id)
            ->where('route_status', 'active')
            ->where('route_from_country', $order->order_from_country)
            ->where('route_start', '>=', $order->order_start)
            ->where('route_end', '<=', $order->order_deadline)
            ->get()
            ->toArray();

        return response()->json([
            'status' => true,
            'count'  => count($routes),
            'result' => null_to_blank($routes),
        ]);
    }
}
