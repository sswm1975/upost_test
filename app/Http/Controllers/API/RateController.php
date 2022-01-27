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
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RateController extends Controller
{
    /**
     * Правила проверки входных данных запроса при создании ставки.
     *
     * @return array
     */
    protected static function rules4saveRate(): array
    {
        return  [
            'route_id' => 'required|integer|exists:routes,id,status,active,user_id,'.request()->user()->id,
            'order_id' => 'required|integer|exists:orders,id,status,active',
            'deadline' => 'required|date|after_or_equal:'.date('Y-m-d'),
            'amount'   => 'required|numeric',
            'currency' => 'required|in:' . implode(',', config('app.currencies')),
            'comment'  => 'required|string|censor|max:1000',
        ];
    }

    /**
     * Создать ставку.
     *
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function addRate(): JsonResponse
    {
        $data = validateOrExit(self::rules4saveRate());

        $is_double = Rate::active()->where(Arr::only($data, ['user_id', 'order_id', 'route_id']))->count();
        if ($is_double) throw new ErrorException(__('message.rate_exists'));

        Rate::create($data);

        return response()->json(['status' => true]);
    }

    /**
     * Изменить ставку.
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function updateRate(int $rate_id): JsonResponse
    {
        if (! $rate = Rate::isOwnerByKey($rate_id)->first()) {
            throw new ErrorException(__('message.rate_not_found'));
        }
        $data = validateOrExit(self::rules4saveRate());

        $affected = $rate->update($data);

        return response()->json(['status' => $affected]);
    }

    /**
     * Просмотр ставки.
     *
     * @param int $rate_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function showRate(int $rate_id): JsonResponse
    {
        $rate = Rate::query()
            ->with([
                'user' => function ($query) {
                    $query->select(User::FIELDS_FOR_SHOW)->withCount('successful_orders');
                },
                'route.from_country',
                'route.from_city',
                'route.to_country',
                'route.to_city',
            ])
            ->whereKey($rate_id)
            ->where(function ($query) {
                return $query->owner()->orWhereExists(function($query) {
                    $query->selectRaw(1)->from('orders')
                        ->whereColumn('orders.id', 'rates.order_id')
                        ->where('user_id', request()->user()->id);
                });
            })
            ->first();

        if (! $rate) throw new ErrorException(__('message.rate_not_found'));

        # если ставка не прочитана и ставку смотрит владелец заказа, то устанавливаем признак просмотра ставки
        if (!$rate->is_read && $rate->user_id <> request()->user()->id) {
            $rate->is_read = true;
            $rate->save();
        }

        return response()->json([
            'status' => true,
            'result' => null_to_blank($rate),
        ]);
    }

    /**
     * Отменить ставку.
     *
     * @param int $rate_id
     * @return JsonResponse
     */
    public function cancelRate(int $rate_id): JsonResponse
    {
        $affected_rows = Rate::IsOwnerByKey($rate_id)->update(['status' => Rate::STATUS_CANCELED]);

        return response()->json([
            'status' => $affected_rows > 0,
        ]);
    }

    /**
     * Удалить ставку.
     *
     * @param int $rate_id
     * @return JsonResponse
     */
    public function deleteRate(int $rate_id): JsonResponse
    {
        $affected = Rate::IsOwnerByKey($rate_id, [Rate::STATUS_ACTIVE, Rate::STATUS_CANCELED])->delete();

        return response()->json([
            'status' => $affected,
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
    public function acceptRate(int $rate_id, Request $request): JsonResponse
    {
        $rate = Rate::query()
            ->with('order:id')
            ->whereKey($rate_id)
            ->active()
            ->whereHas('order', function($query) {
                $query->owner()->active();
            })
            ->first();

        if (! $rate) throw new ErrorException(__('message.rate_not_found'));


//        $rate->status = Rate::STATUS_ACCEPTED;
//        $rate->is_read = true;
//        $rate->save();
        $rate->order()->attach(['status' => Order::STATUS_IN_WORK]);

        return response()->json([
            'status' => true,
            'result' => [__('message.rate_accepted')],
            'sql'=>getSQLForFixDatabase()
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

        $affected = $rate->update(['status' => Rate::STATUS_REJECTED]);

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
            'order_id'  => 'required_without:route_id|integer',
            'route_id'  => 'required_without:order_id|integer',
            'who_start' => 'sometimes|integer',
            'user_id'   => 'sometimes|integer',
            'rate_id'   => 'sometimes|integer',
            'parent_id' => 'sometimes|integer',
        ]);

        $rates = Rate::query()
            ->where('type', $data['rate_type'])
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

        if (!$rates->count()) {
            return response()->json([
                'status' => true,
                'count'  => 0,
            ]);
        }

        $order = Order::query()
            ->with([
                'user:' . implode(',', User::FIELDS_FOR_SHOW),
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->find($rates[0]->order_id);

        $route = Route::query()
            ->with([
                'user:' . implode(',', User::FIELDS_FOR_SHOW),
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->find($rates[0]->route_id);

        $rows = compact('rates', 'order', 'route');

        # находим заказ на мой маршрут (может быть не больше одного заказа)
        if ($data['rate_type'] == 'order') {
            $parent = $rates->firstWhere('parent_id', 0);
            if (!$parent) {
                $parent = Rate::find($rates[0]->parent_id);
            }
            $receiver = Order::find($parent->order_id, ['user_id']);
            $rows = [
                'count'     => $rates->count(),
                'who_start' => $parent->who_start ?? 0,
                'receiver'  => $receiver->user_id ?? 0,
                'parent'    => $parent ?? [],
                'rates'     => $rates,
            ];
        }

        # находим маршруты на мой заказ (их может быть до 3 штук)
        if ($data['rate_type'] == 'route') {
            $parents = $rates->where('parent_id', 0)->all();

            # в выборке нет родителя
            if (count($parents) == 0) {
                $parent = Rate::find($rates[0]->parent_id);
                $receiver = Route::where('route_id', $parent->route_id)->first('user_id')->user_id;
                $rows = [
                    'count'     => $rates->count(),
                    'who_start' => $parent->who_start,
                    'receiver'  => $receiver,
                    'parent'    => $parent,
                    'rates'     => $rates,
                ];

            # в выборке несколько маршрутов на мой заказ - выводим только основные ставки
            } elseif (count($parents) > 1) {
                $rows = ['count' => count($parents), 'rates' => $parents];

            # в выборке только один родитель
            } else {
                $parent = array_shift($parents);
                $receiver = Route::where('route_id', $parent->route_id)->first('user_id')->user_id;
                $rows = [
                    'count'     => $rates->count(),
                    'who_start' => $parent->who_start,
                    'receiver'  => $receiver,
                    'parent'    => $parent,
                    'rates'     => $rates,
                ];
            }
        }

        return response()->json(array_merge(['status' => true], $rows));
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
        $all_rates = Rate::getRatesByOrder($order_id);

        $new_rates = $read_rates = $contr_rates = [];
        foreach ($all_rates as $rates) {
            if (count($rates) == 1) {
                $rate = $rates[0];
                if ($rate->is_read) {
                    $read_rates[] = $rate;
                } else {
                    $new_rates[] = $rate;
                }
                continue;
            }
            $contr_rates[] = $rates->last();
        }

        return response()->json([
            'status'        => true,
            'all_rates'     => null_to_blank($all_rates),
            'new_rates'     => null_to_blank($new_rates),
            'read_rates'    => null_to_blank($read_rates),
            'contr_rates'   => null_to_blank($contr_rates),
            'all_rates_cnt' => count($new_rates) + count($read_rates) + count($contr_rates),
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


}
