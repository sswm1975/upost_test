<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
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
            'route_points'       => 'sometimes|required|array',
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
}
