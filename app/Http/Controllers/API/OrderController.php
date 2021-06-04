<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;

class OrderController extends Controller
{

    /**
     * Сохранить заказ.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
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
     * Валидатор для сохранения заказа.
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
}
