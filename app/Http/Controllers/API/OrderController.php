<?php

namespace App\Http\Controllers\API;

use App\Events\OrderBanned;
use App\Exceptions\ErrorException;
use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Chat;
use App\Models\Country;
use App\Models\Route;
use App\Models\User;
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
    const SORT_FIELDS = [
        'date'  => 'order_register_date',
        'price' => 'order_price_usd',
    ];
    const DEFAULT_SORT_BY = 'date';
    const DEFAULT_SORTING = 'desc';

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
     * Вывод выбранного заказа.
     *
     * @param int $order_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function showOrder(int $order_id, Request $request): JsonResponse
    {
        $order = end($this->getOrdersByFilter($request->user(), compact('order_id'))['data']);

        if (!$order) throw new ErrorException(__('message.order_not_found'));

        $similar_orders = $this->getOrdersByFilter(
            $request->user(),
            [
                'without_order_id' => $order['order_id'],
                'city_from' => [$order['order_from_city']],
                'city_to' => [$order['order_to_city']],
                'show' => 3,
            ]
        )['data'];

        if (empty($similar_orders)) {
            $similar_orders = $this->getOrdersByFilter(
                $request->user(),
                [
                    'without_order_id' => $order['order_id'],
                    'category' => $order['order_category'],
                    'show' => 3,
                ]
            )['data'];
        }

        return response()->json([
            'status' => true,
            'order' => null_to_blank($order),
            'similar_orders' => null_to_blank($similar_orders),
        ]);
    }

    /**
     * Вывод моих заказов.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showMyOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = $this->getOrdersByFilter($user, ['user_id' => $user->user_id])['data'];

        return response()->json([
            'status' => true,
            'orders' => null_to_blank($orders),
            'sql' => getSQLForFixDatabase()
        ]);
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
        $filters = validateOrExit([
            'order_id'       => 'sometimes|required|integer',
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

        $orders = $this->getOrdersByFilter($request->user(), $filters);

        return response()->json([
            'status' => true,
            'count'  => $orders['total'],
            'page'   => $orders['current_page'],
            'pages'  => $orders['last_page'],
            'result' => null_to_blank($orders['data']),
        ]);
    }

    /**
     * Отбор заказов по фильтру.
     *
     * @param User $user
     * @param array $filters
     * @return array
     */
    public function getOrdersByFilter(User $user, array $filters = []): array
    {
        $rate = !empty($filters['currency']) ? Option::rate($filters['currency']) : 1;

        return Order::query()
            ->with([
                'user' => function ($query) {
                    $query->select([
                        'user_id',
                        'user_name',
                        'user_surname',
                        'user_creator_rating',
                        'user_freelancer_rating',
                        'user_photo',
                        'user_favorite_orders',
                        'user_favorite_routes',
                        DB::raw('(select count(*) from `orders` where `users`.`user_id` = `orders`.`user_id` and `order_status` = "successful") as user_successful_orders')
                    ]);
                },
                'category',
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount(['rates as rates_all_count' => function ($query) use ($user) {
                $query->where('parent_id', 0)->where('user_id', $user->user_id);
            }])
            ->withCount(['rates as rates_read_count' => function ($query) use ($user) {
                $query->where('read_rate', 0)->where('user_id', $user->user_id);
            }])
            ->withCount(['rates as is_in_rate' => function ($query) use ($user) {
                $query->typeOrder()->where('user_id', $user->user_id);
            }])
            ->when(!empty($filters['order_id']), function ($query) use ($filters) {
                return $query->where('orders.order_id', $filters['order_id']);
            })
            ->when(!empty($filters['without_order_id']), function ($query) use ($filters) {
                return $query->where('orders.order_id', '!=', $filters['without_order_id']);
            })
            ->when(!empty($filters['user_id']), function ($query) use ($filters) {
                return $query->where('orders.user_id', $filters['user_id']);
            })
            ->when(!empty($filters['category']), function ($query) use ($filters) {
                return $query->where('orders.order_category', $filters['category']);
            })
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                return $query->where('orders.order_status', $filters['status']);
            })
            ->when(!empty($filters['date_from']), function ($query) use ($filters) {
                return $query->where('orders.order_start', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($query) use ($filters) {
                return $query->where('orders.order_start', '<=', $filters['date_to']);
            })
            ->when(!empty($filters['city_from']), function ($query) use ($filters) {
                return $query->whereIn('orders.order_from_city', $filters['city_from']);
            })
            ->when(!empty($filters['city_to']), function ($query) use ($filters) {
                return $query->whereIn('orders.order_to_city', $filters['city_to']);
            })
            ->when(!empty($filters['country_from']), function ($query) use ($filters) {
                return $query->whereIn('orders.order_from_country', $filters['country_from']);
            })
            ->when(!empty($filters['country_to']), function ($query) use ($filters) {
                return $query->whereIn('orders.order_to_country', $filters['country_to']);
            })
            ->when(!empty($filters['price_from']), function ($query) use ($filters, $rate) {
                return $query->where('orders.order_price_usd', '>=', $filters['price_from'] * $rate);
            })
            ->when(!empty($filters['price_to']), function ($query) use ($filters, $rate) {
                return $query->where('orders.order_price_usd', '<=', $filters['price_to'] * $rate);
            })
            ->orderBy(self::SORT_FIELDS[$filters['sort_by'] ?? self::DEFAULT_SORT_BY], $filters['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate($filters['show'] ?? self::DEFAULT_PER_PAGE, ['*'], 'page', $filters['page'] ?? 1)
            ->toArray();
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
     * Увеличить счётчик просмотров заказа.
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

    /**
     * Получить список справочников для фильтра.
     * (Страны; Страны с городами; Минимальная и максимальная цены; Категории товаров)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilters(Request $request): JsonResponse
    {
        if ($request->has('currency')) {
            $currency = getCurrencySymbol($request->get('currency'));

            $prices = Order::toBase()
                ->where('order_currency', $currency)
                ->selectRaw('MIN(order_price) AS price_min, MAX(order_price) AS price_max')
                ->first();
        } else {
            $prices = Order::toBase()
                ->selectRaw('MIN(order_price_usd) AS price_min, MAX(order_price_usd) AS price_max')
                ->first();
        }

        return response()->json([
            'status'                => true,
            'countries'             => Country::getCountries(),
            'countries_with_cities' => Country::getCountriesWithCities(),
            'prices'                => $prices,
            'categories'            => Category::getCategories(),
        ]);
    }
}
