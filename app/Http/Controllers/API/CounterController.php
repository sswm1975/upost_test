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
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 404);
        }

        $post_type = $request->get('post_type');

        if ($post_type == 'order') {
            $model = Order::class;
        } elseif ($post_type == 'route') {
            $model = Route::class;
        } else {
            return response()->json([
                'status' => false,
                'errors' => ['post_type' => 'Error post_type.'],
            ]);
        }

        $object = $model::find($request->get('id'));

        if (empty($object)) {
            return response()->json([
                'status' => 404,
                'errors' => [__('message.' . $post_type == 'order' ? 'order_not_found' : 'route_not_found')],
            ], 404);
        }

        if ($object->user_id == $request->get('user_id')) {
            return response()->json([
                'status'  => true,
            ]);
        }

        $object->increment($post_type . '_look');

        return response()->json([
            'status'  => true,
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
            ]
        );
    }
}
