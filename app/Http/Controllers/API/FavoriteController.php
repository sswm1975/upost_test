<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FavoriteController extends Controller
{
    /**
     * Изменение списка избранных для заказа или маршрута.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function updateFavorite(Request $request): JsonResponse
    {
        validateOrExit([
            'type' => 'required|in:order,route',
            'id'   => 'required|integer',
        ]);

        $user = $request->user();
        $field = 'user_favorite_' . $request->get('type') . 's';
        $favorites = $user->$field ? explode(',', $user->$field) : [];

        if (($key = array_search($request->get('id'), $favorites)) !== false) {
            unset($favorites[$key]);
        } else {
            $favorites[] = $request->get('id');
        }

        $user->$field = count($favorites) ? implode(',', $favorites) : null;
        $user->save();

        return response()->json([
            'status' => true,
            'result' => array_values($favorites),
        ]);
    }

    /**
     * Получить список избранных заказов или маршрутов.
     *
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function showFavorites(): JsonResponse
    {
        $params = validateOrExit(['type' => 'required|in:order,route']);

        $model = 'App\\Models\\' . Str::title($params['type']);

        $rows = $model::getFavorites();

        return response()->json([
            'status' => true,
            'type'   => $params['type'],
            'result' => null_to_blank($rows),
        ]);
    }
}
