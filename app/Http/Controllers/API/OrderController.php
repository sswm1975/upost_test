<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;

class OrderController extends Controller
{
    const DEFAULT_PER_PAGE = 5;
    const DEFAULT_SORT_BY = 'order_register_date';
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
                'errors' => $validator->errors(),
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
                'order_deadline'       => 'required|date',
                'order_personal_price' => 'required|boolean',
                'order_user_price'     => 'required_if:order_personal_price,1',
                'order_user_currency'  => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
                'order_not_more_price' => 'required|boolean',
            ],
            [
                'required'             => 'required_field',
                'max'                  => 'length_filed_greater_than_:max_characters',
                'numeric'              => 'field_must_be_a_number',
                'integer'              => 'field_must_be_a_number',
                'in'                   => 'value_not_exist',
                'exists'               => 'value_not_found',
                'url'                  => 'link_format_is_invalid',
                'date'                 => 'is_not_valid_date',
                'boolean'              => 'field_must_be_1_or_0',
                'required_if'          => 'required_field',
            ]
        );
    }

    /**
     * Удалить заказ.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteOrder(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id'   => 'required|integer',
                'order_id'  => 'required|integer',
            ],
            [
                'required'   => 'required_field',
                'integer'    => 'field_must_be_a_number',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $order = Order::query()
            ->where('order_id', $request->get('order_id'))
            ->where('user_id', $request->get('user_id'))
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
     */
    public function showOrders(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id'      => 'sometimes|required|integer',
                'status'       => 'sometimes|required|in:active,ban',
                'sorting'      => 'sometimes|required|in:asc,desc',
                'sort_by'      => 'sometimes|required|in:date,price',
                'show'         => 'sometimes|required|integer|min:1',
                'page'         => 'sometimes|required|integer|min:1',
                'date_from'    => 'sometimes|required|date',
                'date_to'      => 'sometimes|required|date',
                'city_from'    => 'sometimes|required|integer',
                'city_to'      => 'sometimes|required|integer',
                'country_from' => 'sometimes|required|integer',
                'country_to'   => 'sometimes|required|integer',
                'price_from'   => 'sometimes|required|number',
                'price_to'     => 'sometimes|required|number',
                'currency'     => 'sometimes|required|in:' . implode(',', array_keys(config('app.currencies'))),
            ]
        );

        $data = $validator->validated();

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
            ->orderBy(self::SORT_FIELDS[$data['sort_by'] ?? 'date'], $data['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($data['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $data['page'] ?? 1)
            ->toArray();

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        return response()->json([
            'status' => 200,
            'orders' => null_to_blank($orders),
        ]);
    }
}
