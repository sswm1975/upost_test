<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rate;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RateController extends Controller
{
    /**
     * Создать ставку.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function createRate(Request $request): JsonResponse
    {
        $validator = $this->validator4create($request);

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'params' => $request->all(),
                'query'  => DB::getQueryLog(),
                'errors' => $validator->errors(),
            ]);
        }

        return response()->json([
            'status'  => 200,
            'params' => $request->all(),
            'query'  => DB::getQueryLog(),
        ]);
    }

    /**
     * Валидатор запроса для создания ставки.
     *
     * @param  Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator4create(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        DB::enableQueryLog();

        $validator = Validator::make($request->all(),
            [
                'who_start'     => 'required|integer|exists:users,user_id',
                'rate_type'     => 'required|in:order,route',
                'order_id'      => 'required|integer',
                'route_id'      => 'required|integer',
                'parent_id'     => 'required|integer',
                'rate_text'     => 'required|string',
                'rate_price'    => 'required|numeric',
                'rate_currency' => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        # доп.проверки
        $validator->after(function ($validator) use ($request) {
            # контрставка (parent_id <> 0) должна существовать и быть активной
            if ($request->get('parent_id')) {
                $cnt = Rate::query()
                    ->where('rate_id', $request->get('parent_id'))
                    ->where('rate_status', 'active')
                    ->count();

                if (!$cnt) {
                    $validator->errors()->add('parent_id', 'contrrate_not_exists_or_not_active');
                }
            }

            # указан тип "Заказ"
            if ($request->get('rate_type') == 'order') {
                # заказ должен принадлежать пользователю
                $cnt = Order::query()
                    ->where('order_id', $request->get('order_id'))
                    ->where('user_id', $request->user()->user_id)
                    ->count();

                if (!$cnt) {
                    $validator->errors()->add('order_id', 'not_exists_order');
                }

                # для заказа может быть только одна основная ставка (parent_id = 0)
                if ($request->get('parent_id') == 0) {
                    $cnt = Rate::query()
                        ->where('user_id', $request->user()->user_id)
                        ->where('order_id', $request->get('order_id'))
                        ->where('parent_id', 0)
                        ->count();

                    if ($cnt) {
                        $validator->errors()->add('order_id', 'can_be_only_one_basic_rate_per_order');
                    }
                }
            }

            # указан тип "Маршрут"
            if ($request->get('rate_type') == 'route') {
                # маршрут должен принадлежать пользователю
                $cnt = Route::query()
                    ->where('route_id', $request->get('route_id'))
                    ->where('user_id', $request->user()->user_id)
                    ->count();

                if (!$cnt) {
                    $validator->errors()->add('route_id', 'not_exists_route');
                }

                # для маршрута может быть максимум три основных ставки (parent_id = 0)
                if ($request->get('parent_id') == 0) {
                    $cnt = Rate::query()
                        ->where('user_id', $request->user()->user_id)
                        ->where('route_id', $request->get('route_id'))
                        ->where('parent_id', 0)
                        ->count();

                    if ($cnt > 3) {
                        $validator->errors()->add('route_id', 'can_be_max_three_basic_rate_per_router');
                    }
                }
            }
        });

        return $validator;
    }
}
