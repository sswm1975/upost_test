<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rate;
use App\Models\Route;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $request->merge(['user_id' => $request->user()->user_id]);

        $validator = $this->validator4create($request);

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $rate = Rate::create($request->all());

        return response()->json([
            'status'  => 200,
            'result'  => null_to_blank($rate->toArray()),
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
        $validator = Validator::make($request->all(),
            [
                'who_start'     => 'required|integer',
                'rate_type'     => 'required|in:order,route',
                'order_id'      => 'required|integer',
                'route_id'      => 'required|integer',
                'parent_id'     => 'required|integer',
                'rate_text'     => 'required|string|max:300',
                'rate_deadline' => 'required|date',
                'rate_price'    => 'required|numeric',
                'rate_currency' => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        # если есть ошибки на первичной проверке, то выходим
        if ($validator->fails()) {
            return $validator;
        }

        # доп.проверки
        $validator->after(function ($validator) use ($request) {
            # создатель ставки должен существовать
            if (!User::where('user_id', $request->who_start)->count()) {
                $validator->errors()->add('who_start', 'the_selected_who_start_is_invalid');
            }

            # это основная ставка
            if ($request->parent_id == 0) {
                # создателем первоначальной ставки должен быть авторизированный пользователь
                if ($request->who_start <> $request->user_id) {
                    $validator->errors()->add('who_start', 'for_main_rate_who_start_be_equal_user_id');
                }

                # если указан тип "Заказ"
                if ($request->rate_type == 'order') {
                    # заказ должен существовать
                    if (!Order::where('order_id', $request->order_id)->count()) {
                        $validator->errors()->add('order_id', 'order_not_exists');
                    }

                    # маршрут должен принадлежать пользователю
                    if (!Route::where(['route_id' => $request->route_id, 'user_id' => $request->user_id])->count()) {
                        $validator->errors()->add('route_id', 'route_not_exists');
                    }

                    # для типа "Заказ" может быть только одна основная ставка (parent_id = 0)
                    $cnt = Rate::where(['user_id' => $request->user_id, 'order_id' => $request->order_id, 'parent_id' => 0])->count();
                    if ($cnt) {
                        $validator->errors()->add('order_id', 'can_be_only_one_basic_rate_per_order');
                    }
                }

                # если указан тип "Маршрут"
                if ($request->rate_type == 'route') {
                    # маршрут должен существовать
                    if (!Route::where('route_id', $request->route_id)->count()) {
                        $validator->errors()->add('route_id', 'route_not_exists');
                    }

                    # заказ должен принадлежать пользователю
                    if (!Order::where(['order_id' => $request->order_id, 'user_id' => $request->user_id])->count()) {
                        $validator->errors()->add('order_id', 'order_not_exists');
                    }

                    # для типа "Маршрут" может быть максимум три основных ставки (parent_id = 0)
                    $cnt = Rate::where(['user_id' => $request->user_id, 'route_id' => $request->route_id, 'parent_id' => 0])->count();
                    if ($cnt > 2) {
                        $validator->errors()->add('route_id', 'can_be_max_three_basic_rate_per_router');
                    }
                }
            }

            # это ответ или контрставка
            if ($request->parent_id <> 0) {
                # основная ставка должна существовать и быть активной
                $main_rate = Rate::where(['rate_id' => $request->parent_id, 'rate_status' => 'active'])->first();
                if (!$main_rate) {
                    $validator->errors()->add('parent_id', 'main_rate_not_exists_or_not_active');
                } else {
                    # основные параметры должны соответствовать основной ставке
                    if ($main_rate->who_start <> $request->who_start) {
                        $validator->errors()->add('who_start', 'order_id_is_different_in_main_rate');
                    }
                    if ($main_rate->rate_type <> $request->rate_type) {
                        $validator->errors()->add('rate_type', 'rate_type_is_different_in_main_rate');
                    }
                    if ($main_rate->order_id <> $request->order_id) {
                        $validator->errors()->add('order_id', 'who_start_is_different_in_main_rate');
                    }
                    if ($main_rate->route_id <> $request->route_id) {
                        $validator->errors()->add('route_id', 'route_id_is_different_in_main_rate');
                    }
                }

                # это ответ на ставку
                if ($request->who_start <> $request->user_id) {
                    # заказ должен принадлежать ответчику
                    if ($request->rate_type == 'order') {
                        if (!Order::where(['order_id' => $request->order_id, 'user_id' => $request->user_id])->count()) {
                            $validator->errors()->add('order_id', 'order_not_exists');
                        }
                    }

                    # маршрут должен принадлежать ответчику
                    if ($request->rate_type == 'route') {
                        if (!Route::where(['route_id' => $request->route_id, 'user_id' => $request->user_id])->count()) {
                            $validator->errors()->add('route_id', 'route_not_exists');
                        }
                    }

                # это контрставка
                } else {
                    # создатель контрставки должен быть владельцем основной ставки
                    if ($main_rate && $main_rate->user_id <> $request->user_id) {
                        $validator->errors()->add('parent_id', 'you_not_owner_main_rate');
                    }
                }
            }
        });

        return $validator;
    }

    /**
     * Редактировать ставку.
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateRate(int $rate_id, Request $request): JsonResponse
    {
        $request->merge(['user_id' => $request->user()->user_id]);

        $validator = $this->validator4update($rate_id, $request);

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $rate = Rate::where('rate_id', $rate_id)->first()->fill($validator->validated());
        $rate->save();

        return response()->json([
            'status'  => 200,
            'result'  => null_to_blank($rate->toArray()),
        ]);
    }

    /**
     * Валидатор запроса для обновления ставки.
     *
     * @param int $rate_id
     * @param  Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator4update(int $rate_id, Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $validator = Validator::make($request->all(),
            [
                'rate_text'     => 'required|string|max:300',
                'rate_deadline' => 'required|date',
                'rate_price'    => 'required|numeric',
                'rate_currency' => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        # если есть ошибки на первичной проверке, то выходим
        if ($validator->fails()) {
            return $validator;
        }

        # доп.проверки
        $validator->after(function ($validator) use ($rate_id, $request) {
            $rate = Rate::query()
                ->where([
                    'rate_id' => $rate_id,
                    'user_id' => $request->user_id,
                    'rate_status' => 'active'
                ])
                ->first();
            if (empty($rate)) {
                $validator->errors()->add('rate_id', 'rate_not_found');
            } else {
                if ($rate->parent_id == 0) {
                    $exists_next_rate = Rate::query()
                        ->where('parent_id', $rate_id)
                        ->count();
                } else {
                    $exists_next_rate = Rate::query()
                        ->where('parent_id', $rate->parent_id)
                        ->where('rate_id', '>', $rate_id)
                        ->count();
                }
                if ($exists_next_rate) {
                    $validator->errors()->add('rate_id', 'not_last_rate');
                }
            }
        });

        return $validator;
    }

    /**
     * Удалить ставку.
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteRate(int $rate_id, Request $request): JsonResponse
    {
        $user = $request->user();

        $rate = Rate::query()
            ->where('rate_id', $rate_id)
            ->where('user_id', $user->user_id)
            ->where('rate_status', 'active')
            ->first();

        if (empty($rate)) {
            return response()->json([
                'status' => 404,
                'errors' => 'rate_not_found',
            ]);
        }

        if ($rate->parent_id == 0) {
            $exists_next_rate = Rate::query()
                ->where('parent_id', $rate_id)
                ->count();
        } else {
            $exists_next_rate = Rate::query()
                ->where('parent_id', $rate->parent_id)
                ->where('rate_id', '>', $rate_id)
                ->count();
        }
        if ($exists_next_rate) {
            return response()->json([
                'status' => 404,
                'errors' => 'not_last_rate',
            ]);
        }

        $rate->delete();

        return response()->json([
            'status' => 200,
        ]);
    }

    /**
     * Оклонить ставку.
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectRate(int $rate_id, Request $request): JsonResponse
    {
        $user = $request->user();

        $rate = Rate::query()
            ->where('rate_id', $rate_id)
            ->where('user_id', '<>', $user->user_id)
            ->where('rate_status', 'active')
            ->first();

        if (empty($rate)) {
            return response()->json([
                'status' => 404,
                'errors' => 'rate_not_found',
            ]);
        }

        if ($rate->parent_id == 0) {
            $exists_next_rate = Rate::query()
                ->where('parent_id', $rate_id)
                ->where('user_id', '<>', $user->user_id)
                ->count();
        } else {
            $exists_next_rate = Rate::query()
                ->where('parent_id', $rate->parent_id)
                ->where('user_id', '<>', $user->user_id)
                ->where('rate_id', '>', $rate_id)
                ->count();
        }
        if ($exists_next_rate) {
            return response()->json([
                'status' => 404,
                'errors' => 'not_last_rate',
            ]);
        }

        $rate->update(['rate_status' => 'ban']);

        return response()->json([
            'status' => 200,
        ]);
    }
}
