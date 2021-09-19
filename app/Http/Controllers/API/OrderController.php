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
use App\Models\CurrencyRate;

class OrderController extends Controller
{
    const DEFAULT_PER_PAGE = 5;
    const SORT_FIELDS = [
        'date'  => 'register_date',
        'price' => 'price_usd',
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

        $data = validateOrExit($this->validator($request->all()));

        $order = Order::create($data);

        return response()->json([
            'status'   => true,
            'order_id' => $order->id,
            'url'      => $order->slug,
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
                'name'           => 'required|string|censor|max:100',
                'category_id'    => 'required|integer|exists:categories,id',
                'price'          => 'required|numeric',
                'currency'       => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
                'products_count' => 'required|integer',
                'size'           => 'required|string|max:50',
                'weight'         => 'required|string|max:50',
                'product_link'   => 'sometimes|nullable|string|url',
                'description'    => 'required|string|not_phone|censor|max:500',
                'images'         => 'required|array|max:8',
                'from_country_id'=> 'required|integer|exists:countries,id',
                'from_city_id'   => 'sometimes|required|integer|exists:cities,id,country_id,' . $data['order_from_country'],
                'from_address'   => 'sometimes|nullable|string',
                'to_country_id'  => 'required|integer|exists:countries,country_id',
                'to_city_id'     => 'sometimes|required|integer|exists:cities,id,country_id,' . $data['order_to_country'],
                'to_address'     => 'sometimes|nullable|string',
                'fromdate'       => 'sometimes|required|date',
                'tilldate'       => 'required|date|after_or_equal:fromdate',
                'personal_price' => 'required|boolean',
                'user_price'     => 'required_if:personal_price,1',
                'user_currency'  => 'required|in:' . implode(',', array_keys(config('app.currencies'))),
                'not_more_price' => 'required|boolean',
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
            ->where('user_id', $request->user()->id)
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
                'without_order_id' => $order['id'],
                'city_from' => [$order['from_city_id']],
                'city_to' => [$order['to_city_id']],
                'show' => 3,
            ]
        )['data'];

        if (empty($similar_orders)) {
            $similar_orders = $this->getOrdersByFilter(
                $request->user(),
                [
                    'without_order_id' => $order['id'],
                    'category_id' => $order['category_id'],
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

        $orders = $this->getOrdersByFilter($user, ['user_id' => $user->id])['data'];

        return response()->json([
            'status' => true,
            'orders' => null_to_blank($orders),
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
        $rate = !empty($filters['currency']) ? CurrencyRate::rate($filters['currency']) : 1;

        return Order::query()
            ->with([
                'user' => function ($query) {
                    $query->select([
                        'id',
                        'name',
                        'surname',
                        'creator_rating',
                        'freelancer_rating',
                        'photo',
                        'favorite_orders',
                        'favorite_routes',
                        DB::raw('(select count(*) from `orders` where `users`.`id` = `orders`.`user_id` and `status` = "successful") as successful_orders')
                    ]);
                },
                'category',
                'from_country',
                'from_city',
                'to_country',
                'to_city',
            ])
            ->withCount(['rates as rates_all_count' => function ($query) use ($user) {
                $query->where('parent_id', 0)->where('user_id', $user->id);
            }])
            ->withCount(['rates as rates_read_count' => function ($query) use ($user) {
                $query->where('is_read', 0)->where('user_id', $user->id);
            }])
            ->withCount(['rates as is_in_rate' => function ($query) use ($user) {
                $query->typeOrder()->where('user_id', $user->id);
            }])
            ->when(!empty($filters['order_id']), function ($query) use ($filters) {
                return $query->where('orders.id', $filters['order_id']);
            })
            ->when(!empty($filters['without_order_id']), function ($query) use ($filters) {
                return $query->where('orders.id', '!=', $filters['without_order_id']);
            })
            ->when(!empty($filters['user_id']), function ($query) use ($filters) {
                return $query->where('orders.user_id', $filters['user_id']);
            })
            ->when(!empty($filters['category']), function ($query) use ($filters) {
                return $query->where('orders.category_id', $filters['category']);
            })
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                return $query->where('orders.status', $filters['status']);
            })
            ->when(!empty($filters['date_from']), function ($query) use ($filters) {
                return $query->where('orders.fromdate', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($query) use ($filters) {
                return $query->where('orders.tilldate', '<=', $filters['date_to']);
            })
            ->when(!empty($filters['city_from']), function ($query) use ($filters) {
                return $query->whereIn('orders.from_city_id', $filters['city_from']);
            })
            ->when(!empty($filters['city_to']), function ($query) use ($filters) {
                return $query->whereIn('orders.to_city_id', $filters['city_to']);
            })
            ->when(!empty($filters['country_from']), function ($query) use ($filters) {
                return $query->whereIn('orders.from_country_id', $filters['country_from']);
            })
            ->when(!empty($filters['country_to']), function ($query) use ($filters) {
                return $query->whereIn('orders.to_country_id', $filters['country_to']);
            })
            ->when(!empty($filters['price_from']), function ($query) use ($filters, $rate) {
                return $query->where('orders.price_usd', '>=', $filters['price_from'] * $rate);
            })
            ->when(!empty($filters['price_to']), function ($query) use ($filters, $rate) {
                return $query->where('orders.price_usd', '<=', $filters['price_to'] * $rate);
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
        $data = validateOrExit([
            'chat_id'        => 'required|integer|exists:chats,chat_id',
            'user_id'        => 'required|integer|exists:users,id',
        ]);

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
            'user_id' => 'required|integer|exists:users,id',
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
                ->where('currency', $currency)
                ->selectRaw('MIN(price) AS price_min, MAX(price) AS price_max')
                ->first();
        } else {
            $prices = Order::toBase()
                ->selectRaw('MIN(price_usd) AS price_min, MAX(price_usd) AS price_max')
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
