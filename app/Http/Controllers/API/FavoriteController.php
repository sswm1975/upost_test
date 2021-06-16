<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * Изменение списка избранных для заказа или маршрута.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFavorite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'type' => 'required|in:order,route',
                'id'   => 'required|integer',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 404);
        }

        $user = $request->user();
        $field = 'user_favorite_' . $request->get('type') . 's';
        $favorites = $user->$field ? explode(',', $user->$field) : [];

        if (($key = array_search($request->get('id'), $favorites)) !== false) {
            unset($favorites[$key]);
        } else {
            $favorites[] = $request->get('id');
        }

        $user->$field = implode(',', $favorites);
        $user->save();

        return response()->json([
            'status' => true,
            'result' => array_values($favorites),
        ]);
    }

    /**
     * Получить список избранных заказов или маршрутов.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showFavorites(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'type' => 'required|in:order,route',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 404);
        }

        $type = $request->get('type');
        $field = 'user_favorite_' . $type . 's';
        $favorites = $request->user()->$field ? explode(',', $request->user()->$field) : [];

        if ($type == 'order') {
            $rows = Order::whereIn('order_id', $favorites)->get()->toArray();
        } elseif ($type == 'route') {
            $rows = Route::whereIn('route_id', $favorites)->get()->toArray();
        } else {
            $rows = [];
        }

        return response()->json([
            'status' => true,
            'type'   => $type,
            'result' => null_to_blank($rows),
        ]);
    }
}
