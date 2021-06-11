<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * Добавить в список избранных заказ или маршрут.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFavorite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'type' => 'required|in:order,route',
                'id'   => 'required',
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all(),
            ]);
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
            'status' => 200,
            'result' => array_values($favorites),
        ]);
    }
}
