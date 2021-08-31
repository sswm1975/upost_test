<?php

namespace App\Http\Controllers\API;

use App\Events\OrderBanned;
use App\Exceptions\ErrorException;
use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Route;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /** @var int Количество страйков, за которое выдается бан (заказ переводится в статус ban) */
    const COUNT_STRIKES_FOR_BAN = 50;

    /**
     * Добавить заказ.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     * @throws ValidatorException|ValidationException
     */
    public function addOrder(Request $request): JsonResponse
    {
        if (isProfileNotFilled()) throw new ErrorException(__('message.not_filled_profile'));

        validateOrExit($this->validator($request->all()));

        $order = Order::create($request->all());

        return response()->json([
            'status'   => true,
            'order_id' => $order->order_id,
            'url'      => $order->order_url,
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
                'order_name'           => 'required|string|censor|max:100',
                'order_category'       => 'required|integer|exists:categories,category_id',
                'order_price'          => 'required|numeric',
                'order_currency'       => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
                'order_count'          => 'required|integer',
                'order_size'           => 'required|string|max:50',
                'order_weight'         => 'required|string|max:50',
                'order_product_link'   => 'sometimes|nullable|string|url',
                'order_text'           => 'required|string|not_phone|censor|max:500',
                'order_images'         => 'required|array|max:8',
                'order_from_country'   => 'required|integer|exists:country,country_id',
                'order_from_city'      => 'sometimes|required|integer|exists:city,city_id,country_id,' . $data['order_from_country'],
                'order_from_address'   => 'sometimes|nullable|string',
                'order_to_country'     => 'required|integer|exists:country,country_id',
                'order_to_city'        => 'sometimes|required|integer|exists:city,city_id,country_id,' . $data['order_to_country'],
                'order_to_address'     => 'sometimes|nullable|string',
                'order_start'          => 'sometimes|required|date',
                'order_deadline'       => 'required|date|after_or_equal:order_start',
                'order_personal_price' => 'required|boolean',
                'order_user_price'     => 'required_if:order_personal_price,1',
                'order_user_currency'  => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
                'order_not_more_price' => 'required|boolean',
            ]
        );
    }

    /**
     * Удалить заказ.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function deleteOrder(int $order_id, Request $request): JsonResponse
    {
        $order = Order::query()
            ->where('order_id', $order_id)
            ->where('user_id', $request->user()->user_id)
            ->whereIn('order_status', [
                Order::STATUS_ACTIVE,
                Order::STATUS_BAN,
                Order::STATUS_CLOSED,
            ])
            ->first();

        if (!$order) throw new ErrorException(__('message.order_not_found'));

        $affected = $order->delete();

        return response()->json(['status' => (bool)$affected]);
    }

    /**
     * Вывод заказов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function showOrders(Request $request): JsonResponse
    {
        $data = validateOrExit([
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
        ]);

        $rate = Option::rate($request->get('currency', 'usd'));

        $lang = app()->getLocale();

        $orders = Order::query()
            ->select(
                'orders.*',
                'users.user_name',
                "categories.cat_name_{$lang} AS order_category_name",
                "from_country.country_name_{$lang} AS order_from_country_name",
                "to_country.country_name_{$lang} AS order_to_country_name",
                "from_city.city_name_{$lang} AS order_from_city_name",
                "to_city.city_name_{$lang} AS order_to_city_name",
                DB::raw('IFNULL(LENGTH(users.user_favorite_orders) - LENGTH(REPLACE(users.user_favorite_orders, ",", "")) + 1, 0) AS cnt_favorite_orders')
            )
            ->join('users', 'users.user_id', 'orders.user_id')
            ->join('categories', 'categories.category_id', 'orders.order_category')
            ->leftJoin('country AS from_country', 'from_country.country_id', 'orders.order_from_country')
            ->leftJoin('country AS to_country', 'to_country.country_id', 'orders.order_to_country')
            ->leftJoin('city AS from_city', 'from_city.city_id', 'orders.order_from_city')
            ->leftJoin('city AS to_city', 'to_city.city_id', 'orders.order_to_city')
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

        return response()->json([
            'status' => true,
            'count'  => $orders['total'],
            'page'   => $orders['current_page'],
            'pages'  => $orders['last_page'],
            'result' => null_to_blank($orders['data']),
        ]);
    }

    /**
     * Подобрать маршрут для заказа.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function selectionRoute(int $order_id, Request $request):JsonResponse
    {
        if (!$order = Order::find($order_id)) {
            throw new ErrorException(__('message.order_not_found'));
        }

        $routes = Route::query()
            ->where('user_id', $request->user()->user_id)
            ->where('route_status', Route::STATUS_ACTIVE)
            ->where('route_from_country', $order->order_from_country)
            ->where('route_start', '>=', $order->order_start)
            ->where('route_end', '<=', $order->order_deadline)
            ->get()
            ->toArray();

        return response()->json([
            'status' => true,
            'count'  => count($routes),
            'result' => null_to_blank($routes),
        ]);
    }

    /**
     * Подтвердить выполнение заказа.
     *
     * @param Request $request
     * @return Request
     * @throws ValidatorException
     * @throws TryException
     */
    public function confirmOrder(Request $request): JsonResponse
    {
        $validator = Validator::make(request()->all(),
            [
                'chat_id'        => 'required|integer|exists:chats,chat_id',
                'user_id'        => 'required|integer|exists:users,user_id',
            ]
        );
        validateOrExit($validator);

        $data = $request->all();

        try {
            $this->confirmExtValidate($data);
        }
        catch (Exception $e) {
            throw new TryException($e->getMessage());
        }

        return response()->json([
            'status' => true
        ]);
    }

    /**
     * confirm order validation
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    private function confirmExtValidate(array $data): bool
    {
        $query = (new Chat())->newQuery();
        $count = $query->where('chat_id', $data['chat_id'])
            ->where('user_id', $data['user_id'])
            ->count();

        if($count == 0) {
            throw new Exception("This chat belongs to other user");
        }

        return true;
    }

    /**
     * Пожаловаться на заказ.
     *
     * @param int $order_id
     * @param Request $request
     * @throws ValidatorException|ErrorException|ValidationException
     */
    public function strikeOrder(int $order_id, Request $request): JsonResponse
    {
        validateOrExit([
            'strike_id' => 'required|integer',
        ]);

        if (!$order = Order::find($order_id)) {
            throw new ErrorException(__('message.order_not_found'));
        }

        $user_id = $request->user()->user_id;

        $strikes = $order->order_strikes;
        if ($strikes && array_key_exists($user_id, $strikes)) {
            throw new ErrorException(__('message.already_have_complaint'));
        }
        $strikes[$user_id] = $request->get('strike_id');
        $order->order_strikes = $strikes;

        if (count($strikes) >= static::COUNT_STRIKES_FOR_BAN) {
            $order->order_status = Order::STATUS_BAN;

            event(new OrderBanned($order));
        }

        $order->save();

        return response()->json([
            'status' => true
        ]);
    }

    /**
     * Увеличить счетчик просмотров заказа.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function addLook(int $order_id, Request $request): JsonResponse
    {
        validateOrExit([
            'user_id' => 'required|integer|exists:users,user_id',
        ]);

        if (!$order = Order::find($order_id)) {
            throw new ErrorException(__('message.order_not_found'));
        }

        if ($order->user_id <> $request->get('user_id')) {
            $order->increment('order_look');
        }

        return response()->json([
            'status' => true,
            'looks'  => $order->order_look,
        ]);
    }
}
