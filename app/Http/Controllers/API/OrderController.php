<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Order;
use App\Models\Option;

class OrderController extends Controller
{
    const DEFAULT_PER_PAGE = 5;
    const DEFAULT_SORTING = 'desc';
    const SORT_FIELDS = [
        'date'  => 'order_register_date',
        'price' => 'order_price_usd',
    ];

    /**
     * Сохранить заказ.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveOrder(Request $request): JsonResponse
    {
        $user = $GLOBALS['user'];
        $request->merge(['user_id' => $user->user_id]);

        # Якшо не заповнені ім’я, прізвищі, дата народження – то заказ розмістити неможливо.
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

        $order = Order::create($request->all());

        return response()->json([
            'status'  => 200,
            'url'     => $order->order_url,
        ]);
    }

    /**
     * Валидатор запроса с данными заказа.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data,
            [
                'order_name'           => 'required|string|max:100',
                'order_category'       => 'required|integer|exists:categories,category_id',
                'order_price'          => 'required|numeric',
                'order_price_usd'      => 'required|numeric',
                'order_currency'       => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
                'order_count'          => 'required|integer',
                'order_size'           => 'required|string|max:50',
                'order_weight'         => 'required|string|max:50',
                'order_product_link'   => 'sometimes|nullable|string|url',
                'order_text'           => 'required|string|max:500',
                'order_images'         => 'required|array|max:8',
                'order_from_country'   => 'required|integer|exists:country,country_id',
                'order_from_city'      => 'sometimes|required|integer|exists:city,city_id,country_id,' . $data['order_from_country'],
                'order_from_address'   => 'sometimes|nullable|string',
                'order_to_country'     => 'required|integer|exists:country,country_id',
                'order_to_city'        => 'sometimes|required|integer|exists:city,city_id,country_id,' . $data['order_to_country'],
                'order_to_address'     => 'sometimes|nullable|string',
                'order_start'          => 'required|date',
                'order_deadline'       => 'required|date|after_or_equal:order_start',
                'order_personal_price' => 'required|boolean',
                'order_user_price'     => 'required_if:order_personal_price,1',
                'order_user_currency'  => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
                'order_not_more_price' => 'required|boolean',
            ],
            config('validation.messages'),
            config('validation.attributes')
        );
    }

    /**
     * Удалить заказ.
     *
     * @param int $order_id
     * @return JsonResponse
     */
    public function deleteOrder(int $order_id): JsonResponse
    {
        $user = $GLOBALS['user'];

        $order = Order::query()
            ->where('order_id', $order_id)
            ->where('user_id', $user->user_id)
            ->whereIn('order_status', ['active', 'ban', 'closed'])
            ->first();

        if (empty($order)) {
            return response()->json([
                'status' => 404,
                'errors' => 'order_not_found',
            ]);
        }

        $order->delete();

        return response()->json([
            'status' => 200,
        ]);
    }

    /**
     * Вывод заказов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function showOrders(Request $request): JsonResponse
    {
        $validator = Validator::make(request()->all(),
            [
                'user_id'        => 'sometimes|required|integer',
                'status'         => 'sometimes|required|in:active,ban',
                'sorting'        => 'sometimes|required|in:asc,desc',
                'sort_by'        => 'sometimes|required|in:date,price',
                'show'           => 'sometimes|required|integer|min:1',
                'page'           => 'sometimes|required|integer|min:1',
                'date_from'      => 'sometimes|required|date',
                'date_to'        => 'sometimes|required|date|after_or_equal:date_from',
                'city_from'      => 'sometimes|required|array',
                'city_from.*'    => 'required|integer',
                'city_to'        => 'sometimes|required|array',
                'city_to.*'      => 'required|integer',
                'country_from'   => 'sometimes|required|array',
                'country_from.*' => 'required|integer',
                'country_to'     => 'sometimes|required|array',
                'country_to.*'   => 'required|integer',
                'price_from'     => 'sometimes|required|numeric',
                'price_to'       => 'sometimes|required|numeric',
                'currency'       => 'sometimes|required|in:' . implode(',', array_keys(config('app.currencies'))),
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all()
            ]);
        }

        $data = $validator->validated();

//        DB::enableQueryLog();

        $rate = Option::rate($request->get('currency', 'usd'));

        $orders = Order::query()
            ->when($request->filled('user_id'), function ($query) use ($data) {
                return $query->where('user_id', $data['user_id']);
            })
            ->when($request->filled('status'), function ($query) use ($data) {
                return $query->where('order_status', $data['status']);
            })
            ->when($request->filled('date_from'), function ($query) use ($data) {
                return $query->where('order_start', '>=', $data['date_from']);
            })
            ->when($request->filled('date_to'), function ($query) use ($data) {
                return $query->where('order_start', '<=', $data['date_to']);
            })
            ->when($request->filled('city_from'), function ($query) use ($data) {
                return $query->whereIn('order_from_city', $data['city_from']);
            })
            ->when($request->filled('city_to'), function ($query) use ($data) {
                return $query->whereIn('order_to_city', $data['city_to']);
            })
            ->when($request->filled('country_from'), function ($query) use ($data) {
                return $query->whereIn('order_from_country', $data['country_from']);
            })
            ->when($request->filled('country_to'), function ($query) use ($data) {
                return $query->whereIn('order_to_country', $data['country_to']);
            })
            ->when($request->filled('price_from'), function ($query) use ($data, $rate) {
                return $query->where('order_price_usd', '>=', $data['price_from'] * $rate);
            })
            ->when($request->filled('price_to'), function ($query) use ($data, $rate) {
                return $query->where('order_price_usd', '<=', $data['price_to'] * $rate);
            })
            ->orderBy(self::SORT_FIELDS[$data['sort_by'] ?? 'date'], $data['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($data['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $data['page'] ?? 1)
            ->toArray();

/* For testing:
        return response()->json([
            'data'   => $data,
            'rate'   => $rate,
            'query'  => DB::getQueryLog(),
            'orders' => null_to_blank($orders),
        ]);
*/

        return response()->json([
            'status' => 200,
            'count'  => $orders['total'],
            'page'   => $orders['current_page'],
            'pages'  => $orders['last_page'],
            'result' => null_to_blank($orders['data']),
        ]);
    }

    /**
     * Подобрать заказ.
     *
     * @param int $route_id
     * @return JsonResponse
     */
    public function selectionOrder(int $route_id):JsonResponse
    {
        $user = $GLOBALS['user'];

        $route = Route::find($route_id);

        if (empty($route)) {
            return response()->json([
                'status' => 404,
                'errors' => 'route_not_found',
            ]);
        }

        $orders = Order::query()
            ->where('user_id', $user->user_id)
            ->where('order_status', 'active')
            ->where('order_from_country', $route->route_from_country)
            ->where('order_start', '>=', $route->route_start)
            ->where('order_deadline', '<=', $route->route_end)
            ->get()
            ->toArray();

        return response()->json([
            'status' => 200,
            'count'  => count($orders),
            'result' => null_to_blank($orders),
        ]);
    }
}
