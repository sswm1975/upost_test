<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user = $GLOBALS['user'];
        $request->merge(['user_id' => $user->user_id]);

        # Якшо ім'я, прізвище, дата народження не заповнені - то не давати створити маршрут.
        if (empty($user->user_name) || empty($user->user_surname) || empty($user->user_birthday)) {
            return response()->json([
                'status' => 404,
                'errors' => 'in_profile_not_fill_name_or_surname_or_birthday',
            ]);
        }
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all(),
            ]);
        }

        $route = Route::create($request->all());

        return response()->json([
            'status'  => 200,
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
            'route_from_city'    => 'sometimes|required|integer|exists:city,city_id,country_id,' . $data['route_from_country'],
            'route_to_country'   => 'required|integer|exists:country,country_id',
            'route_to_city'      => 'sometimes|required|integer|exists:city,city_id,country_id,' . $data['route_to_country'],
            'route_start'        => 'required|date',
            'route_end'          => 'required|date|after_or_equal:route_start',
            'route_transport'    => 'required|in:car,bus,walk,train,plane',
            'route_points'       => 'sometimes|nullable|array',
        ];

        $attributes = config('validation.attributes');
        for($i = 0; $i < count($data['route_points'] ?? []); $i++) {
            $country_id = (int)($data['route_points'][$i]['country'] ?? 0);
            $rules["route_points.{$i}.country"] = 'sometimes|required|integer|exists:country,country_id';
            $rules["route_points.{$i}.city"]    = 'sometimes|required|integer|exists:city,city_id,country_id,' . $country_id;
            $rules["route_points.{$i}.date"]    = 'sometimes|required|date';
            $attributes["route_points.{$i}.country"] = "route_points.{$i}.country";
            $attributes["route_points.{$i}.city"]    = "route_points.{$i}.city";
            $attributes["route_points.{$i}.date"]    = "route_points.{$i}.date";
        }

        return Validator::make($data, $rules, config('validation.messages'), $attributes);
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
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all()
            ]);
        }

        $data = $validator->validated();

//        DB::enableQueryLog();

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

//        return response()->json([
//            'data'   => $data,
//            'query'  => DB::getQueryLog(),
//            'orders' => null_to_blank($routes),
//        ]);

        return response()->json([
            'status' => 200,
            'count'  => $routes['total'],
            'page'   => $routes['current_page'],
            'pages'  => $routes['last_page'],
            'result' => null_to_blank($routes['data']),
        ]);
    }

    /**
     * Редактирование маршрута.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateRoute(int $id, Request $request): JsonResponse
    {
        $user = $GLOBALS['user'];

        # Якшо ім'я, прізвище, дата народження не заповнені - то не давати створити маршрут.
        if (empty($user->user_name) || empty($user->user_surname) || empty($user->user_birthday)) {
            return response()->json([
                'status' => 404,
                'errors' => 'in_profile_not_fill_name_or_surname_or_birthday',
            ]);
        }

        # Ищем маршрут по его коду, он должен принадлежать авторизированному пользователю и быть активным
        $route = Route::query()
            ->where('route_id', $id)
            ->where('user_id', $user->user_id)
            ->where('route_status', 'active')
            ->first();

        if (empty($route)) {
            return response()->json([
                'status' => 404,
                'errors' => 'route_not_found',
            ]);
        }

        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all()
            ]);
        }

        $route->update($validator->validated());

        return response()->json([
            'status'  => 200,
            'result'  => null_to_blank($route->toArray()),
        ]);
    }
}
