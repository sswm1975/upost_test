<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Route;
use Illuminate\Support\Facades\Validator;

class CounterController extends Controller
{
    /**
     * Счетчик просмотров для заказов и маршрутов.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->get('post_type') == 'order') {
            $model = Order::class;
        } elseif ($request->get('post_type') == 'route') {
            $model = Route::class;
        } else {
            return response()->json([
                'status' => 200,
            ]);
        }

        $object = $model::find($request->get('id'));

        if (empty($object)) {
            return response()->json([
                'status' => 404,
                'errors' => 'record_not_found',
            ]);
        }

        if ($object->user_id == $request->get('user_id')) {
            return response()->json([
                'status'  => 200,
            ]);
        }

        $object->increment($request->get('post_type') . '_look');

        return response()->json([
            'status'  => 200,
        ]);
    }

    /**
     * Валидатор для проверки входных данных.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data,
            [
                'post_type' => 'required|in:order,route',
                'user_id'   => 'required|integer|exists:users,user_id',
                'id'        => 'required',
            ],
            [
                'required'  => 'required_field',
                'in'        => 'value_not_exist',
                'exists'    => 'value_not_found',
            ]
        );
    }
}
