<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
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
     * @throws ValidatorException|ValidationException
     */
    public function addRate(Request $request): JsonResponse
    {
        validateOrExit($this->validator4add($request));

        $rate = Rate::create($request->all());

        return response()->json([
            'status' => true,
            'result' => null_to_blank($rate->toArray()),
        ]);
    }

    /**
     * Валидатор запроса для создания ставки.
     *
     * @param  Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator4add(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $validator = Validator::make($request->all(),
            [
                'who_start' => 'required|integer',
                'rate_type' => 'required|in:order,route',
                'order_id'  => 'required|integer',
                'route_id'  => 'required|integer',
                'parent_id' => 'required|integer',
                'text'      => 'required|string|max:300',
                'deadline'  => 'required|date',
                'price'     => 'required|numeric',
                'currency'  => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
            ]
        );

        # если есть ошибки на первичной проверке, то выходим
        if ($validator->fails()) {
            return $validator;
        }

        # доп.проверки
        $validator->after(function ($validator) use ($request) {
            $user_id = $request->user()->id;
            # создатель ставки должен существовать
            if (!User::whereKey($request->who_start)->count()) {
                $validator->errors()->add('who_start', __('message.user_not_found'));
            }

            # это основная ставка
            if ($request->parent_id == 0) {
                # создателем первоначальной ставки должен быть авторизированный пользователь
                if ($request->who_start <> $user_id) {
                    $validator->errors()->add('who_start', __('message.who_start_incorrect'));
                }

                # если указан тип "Заказ"
                if ($request->type == 'order') {
                    # заказ должен существовать
                    if (!Order::whereKey($request->order_id)->count()) {
                        $validator->errors()->add('order_id', __('message.order_not_found'));
                    }

                    # маршрут должен принадлежать пользователю
                    if (!Route::where(['id' => $request->route_id, 'user_id' => $user_id])->count()) {
                        $validator->errors()->add('route_id', __('message.route_not_found'));
                    }

                    # для типа "Заказ" может быть только одна основная ставка (parent_id = 0)
                    $cnt = Rate::where(['user_id' => $user_id, 'order_id' => $request->order_id, 'parent_id' => 0])->count();
                    if ($cnt) {
                        $validator->errors()->add('order_id', __('message.one_rate_per_order'));
                    }
                }

                # если указан тип "Маршрут"
                if ($request->type == 'route') {
                    # маршрут должен существовать
                    if (!Route::whereKey($request->route_id)->count()) {
                        $validator->errors()->add('route_id', __('message.route_not_found'));
                    }

                    # заказ должен принадлежать пользователю
                    if (!Order::where(['order_id' => $request->order_id, 'user_id' => $user_id])->count()) {
                        $validator->errors()->add('order_id', __('message.order_not_found'));
                    }

                    # для типа "Маршрут" может быть максимум три основных ставки (parent_id = 0)
                    $cnt = Rate::where(['user_id' => $user_id, 'route_id' => $request->route_id, 'parent_id' => 0])->count();
                    if ($cnt > 2) {
                        $validator->errors()->add('route_id', __('message.three_rate_per_route'));
                    }
                }
            }

            # это ответ или контрставка
            if ($request->parent_id <> 0) {
                # основная ставка должна существовать и быть активной
                $main_rate = Rate::where(['id' => $request->parent_id, 'status' => 'active'])->first();
                if (!$main_rate) {
                    $validator->errors()->add('parent_id', __('message.rate_not_found'));
                } else {
                    # основные параметры должны соответствовать основной ставке
                    if ($main_rate->who_start <> $request->who_start) {
                        $validator->errors()->add('who_start', __('message.differs_from_basic_rate'));
                    }
                    if ($main_rate->type <> $request->type) {
                        $validator->errors()->add('type', __('message.differs_from_basic_rate'));
                    }
                    if ($main_rate->order_id <> $request->order_id) {
                        $validator->errors()->add('order_id', __('message.differs_from_basic_rate'));
                    }
                    if ($main_rate->route_id <> $request->route_id) {
                        $validator->errors()->add('route_id', __('message.differs_from_basic_rate'));
                    }
                }

                # это ответ на ставку
                if ($request->who_start <> $user_id) {
                    # заказ должен принадлежать ответчику
                    if ($request->type == 'order') {
                        if (!Order::where(['id' => $request->order_id, 'user_id' => $user_id])->count()) {
                            $validator->errors()->add('order_id', __('message.order_not_found'));
                        }
                    }

                    # маршрут должен принадлежать ответчику
                    if ($request->rate_type == 'route') {
                        if (!Route::where(['id' => $request->route_id, 'user_id' => $user_id])->count()) {
                            $validator->errors()->add('route_id', __('message.route_not_found'));
                        }
                    }

                # это контрставка
                } else {
                    # создатель контрставки должен быть владельцем основной ставки
                    if ($main_rate && $main_rate->user_id <> $user_id) {
                        $validator->errors()->add('parent_id', __('message.not_owner_basic_rate'));
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
     * @throws ValidationException|ValidatorException
     */
    public function updateRate(int $rate_id, Request $request): JsonResponse
    {
        $data = validateOrExit($this->validator4update($rate_id, $request));

        $rate = Rate::whereKey($rate_id)->first()->fill($data);
        $rate->save();

        return response()->json([
            'status'  => true,
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
                'text'     => 'required|string|max:300',
                'deadline' => 'required|date',
                'price'    => 'required|numeric',
                'currency' => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
            ]
        );

        # если есть ошибки на первичной проверке, то выходим
        if ($validator->fails()) {
            return $validator;
        }

        # доп.проверки
        $validator->after(function ($validator) use ($rate_id, $request) {
            $rate = Rate::query()
                ->where([
                    'id' => $rate_id,
                    'user_id' => $request->user()->id,
                    'status' => 'active'
                ])
                ->first();
            if (empty($rate)) {
                $validator->errors()->add('id', __('message.rate_not_found'));
            } else {
                if ($rate->parent_id == 0) {
                    $exists_next_rate = Rate::query()
                        ->where('parent_id', $rate_id)
                        ->count();
                } else {
                    $exists_next_rate = Rate::query()
                        ->where('parent_id', $rate->parent_id)
                        ->where('id', '>', $rate_id)
                        ->count();
                }
                if ($exists_next_rate) {
                    $validator->errors()->add('id', __('message.not_last_rate'));
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
     * @throws ErrorException
     */
    public function deleteRate(int $rate_id, Request $request): JsonResponse
    {
        $rate = Rate::query()
            ->where('id', $rate_id)
            ->where('user_id', $request->user()->user_id)
            ->where('status', Rate::STATUS_ACTIVE)
            ->first();

        if (!$rate) throw new ErrorException(__('message.rate_not_found'));

        if ($rate->parent_id == 0) {
            $exists_next_rate = Rate::query()
                ->where('parent_id', $rate_id)
                ->count();
        } else {
            $exists_next_rate = Rate::query()
                ->where('parent_id', $rate->parent_id)
                ->where('id', '>', $rate_id)
                ->count();
        }
        if ($exists_next_rate) throw new ErrorException(__('message.not_last_rate'));

        $affected = $rate->delete();

        return response()->json([
            'status' => (bool)$affected,
        ]);
    }

    /**
     * Оклонить ставку.
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function rejectRate(int $rate_id, Request $request): JsonResponse
    {
        $user = $request->user();

        $rate = Rate::query()
            ->where('id', $rate_id)
            ->where('user_id', '<>', $user->id)
            ->where('status', Rate::STATUS_ACTIVE)
            ->first();

        if (!$rate) throw new ErrorException(__('message.rate_not_found'));

        if ($rate->parent_id == 0) {
            $exists_next_rate = Rate::query()
                ->where('parent_id', $rate_id)
                ->where('user_id', '<>', $user->id)
                ->count();
        } else {
            $exists_next_rate = Rate::query()
                ->where('parent_id', $rate->parent_id)
                ->where('user_id', '<>', $user->id)
                ->where('id', '>', $rate_id)
                ->count();
        }
        if ($exists_next_rate) throw new ErrorException(__('message.not_last_rate'));

        $affected = $rate->update(['status' => Rate::STATUS_BAN]);

        return response()->json([
            'status' => (bool)$affected,
        ]);
    }

    /**
     * Получить ставки.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function showRates(Request $request):JsonResponse
    {
        $data = validateOrExit([
            'rate_type' => 'required|in:order,route',
            'user_id'   => 'sometimes|integer',
            'rate_id'   => 'sometimes|integer',
            'order_id'  => 'required_without:route_id|integer',
            'route_id'  => 'required_without:order_id|integer',
            'parent_id' => 'sometimes|integer',
            'who_start' => 'sometimes|integer',
        ]);

        $rates = Rate::query()
            ->where('type', $request->rate_type)
            ->when($request->filled('user_id'), function ($query) use ($data) {
                return $query->where('user_id', $data['user_id']);
            })
            ->when(!$request->filled('rate_id') && $request->filled('order_id'), function ($query) use ($data) {
                return $query->where('order_id', $data['order_id']);
            })
            ->when(!$request->filled('rate_id') && $request->filled('route_id'), function ($query) use ($data) {
                return $query->where('route_id', $data['route_id']);
            })
            ->when($request->filled('rate_id'), function ($query) use ($data) {
                return $query->whereKey($data['rate_id']);
            })
            ->when($request->filled('parent_id'), function ($query) use ($data) {
                return $query->where('parent_id', $data['parent_id']);
            })
            ->when($request->filled('who_start'), function ($query) use ($data) {
                return $query->where('who_start', $data['who_start']);
            })
            ->orderByDesc('id')
            ->get();

        $data = ['count' => 0, 'result' => []];

        if ($rates->count()) {
            # находим заказ на мой маршрут (может быть не больше одного заказа)
            if ($request->rate_type == 'order') {
                $parent = $rates->firstWhere('parent_id', 0);
                if (!$parent) {
                    $parent = Rate::find($rates[0]->parent_id);
                }
                $receiver = Order::find($parent->order_id, ['user_id']);
                $data = [
                    'count'     => $rates->count(),
                    'who_start' => $parent->who_start ?? 0,
                    'receiver'  => $receiver->user_id ?? 0,
                    'parent'    => $parent ?? [],
                    'result'    => $rates,
                ];
            }

            # находим маршруты на мой заказ (их может быть до 3 штук)
            if ($request->rate_type == 'route') {
                $parents = $rates->where('parent_id', 0)->all();

                # в выборке нет родителя
                if (count($parents) == 0) {
                    $parent = Rate::find($rates[0]->parent_id);
                    $receiver = Route::where('route_id', $parent->route_id)->first('user_id')->user_id;
                    $data = [
                        'count'     => $rates->count(),
                        'who_start' => $parent->who_start,
                        'receiver'  => $receiver,
                        'parent'    => $parent,
                        'result'    => $rates,
                    ];

                # в выборке несколько маршрутов на мой заказ - выводим только основные ставки
                } elseif (count($parents) > 1) {
                    $data = ['count' => count($parents), 'result' => $parents];

                # в выборке только один родитель
                } else {
                    $parent = array_shift($parents);
                    $receiver = Route::where('route_id', $parent->route_id)->first('user_id')->user_id;
                    $data = [
                        'count'     => $rates->count(),
                        'who_start' => $parent->who_start,
                        'receiver'  => $receiver,
                        'parent'    => $parent,
                        'result'    => $rates,
                    ];
                }
            }
        }

        return response()->json(array_merge(['status' => true], $data));
    }

    /**
     * Получить ставки по выбранному заказу.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     */
    public function showRatesByOrder(int $order_id, Request $request):JsonResponse
    {
        $new_rates = Rate::getNewRatesByOrder($order_id);
        $read_rates = Rate::getReadRatesByOrder($order_id);
        $exists_child_rates = Rate::getExistsChildRatesByOrder($order_id);
        $rates_all = count($new_rates) +  count($read_rates) + count($exists_child_rates);

        return response()->json([
            'status' => true,
            'new_rates' => null_to_blank($new_rates),
            'read_rates' => null_to_blank($read_rates),
            'exists_child_rates' => null_to_blank($exists_child_rates),
            'rates_all' => $rates_all,
        ]);
    }

    /**
     * Получить ставки по выбранному маршруту.
     *
     * @param int $route_id
     * @param Request $request
     * @return JsonResponse
     */
    public function showRatesByRoute(int $route_id, Request $request):JsonResponse
    {
        $new_rates = Rate::getNewRatesByRoute($route_id);
        $read_rates = Rate::getReadRatesByRoute($route_id);
        $exists_child_rates = Rate::getExistsChildRatesByRoute($route_id);
        $rates_all = count($new_rates) +  count($read_rates) + count($exists_child_rates);

        return response()->json([
            'status' => true,
            'new_rates' => null_to_blank($new_rates),
            'read_rates' => null_to_blank($read_rates),
            'exists_child_rates' => null_to_blank($exists_child_rates),
            'rates_all' => $rates_all,
        ]);
    }

    /**
     * Получить ставку.
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function showRate(int $rate_id, Request $request):JsonResponse
    {
        if (!$rate = Rate::find($rate_id)) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        $rate->is_read = true;
        $rate->save();

        return response()->json([
            'status' => true,
            'result' => null_to_blank($rate->toArray()),
        ]);
    }

    /**
     * Принять ставку.
     *
     * @param int $rate_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function acceptRate(int $rate_id, Request $request):JsonResponse
    {
        $rate = Rate::query()
            ->with('route:id,user_id', 'order:id,user_id')
            ->whereKey($rate_id)
            ->whereStatus(Rate::STATUS_ACTIVE)
            ->first();

        if (!$rate) throw new ErrorException(__('message.rate_not_found'));

        $user_id = $request->user()->id;

        # Условия подтверждения ставки:
        # - при ставке на заказ подтвердить может только владелец маршрута
        # - при ставке на машртут подтвердить может только владелец заказа
        # - свои ставки подтверждать запрещено
        if ($rate->who_start == $rate->user_id) {
            $accept = ($rate->rate_type == 'order' && $rate->order->user_id == $user_id) || ($rate->type == 'route' && $rate->route->user_id == $user_id);
        } else {
            $accept = ($rate->rate_type == 'order' && $rate->route->user_id == $user_id) || ($rate->type == 'route' && $rate->order->user_id == $user_id);
        }

        if (!$accept) throw new ErrorException(__('message.rate_not_accepted'));

        # существуют ещё ставки?
        if ($rate->parent_id == 0) {
            $exists_next_rate = Rate::where('parent_id', $rate_id)->count();
        } else {
            $exists_next_rate = Rate::where('parent_id', $rate->parent_id)->where('rate_id', '>', $rate_id)->count();
        }
        if ($exists_next_rate) throw new ErrorException(__('message.not_last_rate'));

        $rate->status = Rate::STATUS_PROGRESS;
        $rate->save();

        return response()->json([
            'status' => true,
            'result' => [__('message.rate_accepted')],
        ]);
    }
}
