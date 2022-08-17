<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Rate;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Добавить отзыв.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function addReview(Request $request): JsonResponse
    {
        $user_id = request()->user()->id;

        $data = validateOrExit([
            'rate_id' => 'required|integer',
            'scores'  => 'required|integer|min:1|max:5',
            'text'    => 'required|string|max:500',
        ]);

        # запрещаем дублировать отзыв
        $is_double = Review::owner()->whereRateId($data['rate_id'])->count();
        if ($is_double) throw new ErrorException(__('message.review_add_double'));

        # авторизированный пользователь должен быть владельцем заказа или маршрута, а ставка быть в статусе Успешная или Завершенная
        $rate = Rate::whereKey($data['rate_id'])
            ->whereIn('status', [Rate::STATUS_SUCCESSFUL, Rate::STATUS_DONE])
            ->where(function ($query) {
                return $query->owner()->orWhereHas('order', function($query) {
                    $query->owner();
                });
            })
            ->first(['id', 'user_id', 'order_id']);

        # отзыв может оставлять только владелец заказа или маршрута
        if (! $rate) throw new ErrorException(__('message.review_not_allowed'));
        $rate->setAppends([]);

        # определяем, кому делаем отзыв
        if ($user_id == $rate->user_id) {
            # получатель отзыва будет владелец Заказа
            $data['recipient_id'] = Order::find($rate->order_id, ['user_id'])->user_id;
            $data['recipient_type'] = Review::USER_TYPE_CUSTOMER;
        } else {
            # получатель отзыва будет владелец Маршрута
            $data['recipient_id'] = $rate->user_id;
            $data['recipient_type'] = Review::USER_TYPE_PERFORMER;
        }

        # создаем отзыв
        Review::create($data);

        # получателю отзыва увеличиваем рейтинг
        User::whereKey($data['recipient_id'])
            ->update([
                'reviews_count' => DB::raw('reviews_count + 1'),
                'scores_count'  => DB::raw("scores_count + {$data['scores']}"),
            ]);

        return response()->json([
            'status' => true,
            'rate'   => $rate,
        ]);
    }

    /**
     * Получить отзывы.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function showReviews(Request $request): JsonResponse
    {
        # валидируем
        $data = validateOrExit([
            'order_id'       => 'integer',
            'author_id'      => 'integer',
            'recipient_id'   => 'integer',
            'recipient_type' => 'nullable|in:customer,performer',
            'sorting'        => 'sometimes|nullable|in:asc,desc',
        ]);

        # не указан ни один параметр, то отдаем пустой массив
        $not_params = $request->isNotFilled(['order_id', 'author_id', 'recipient_id']);
        if ($not_params && empty($data['user_id'])) {
            return response()->json([
                'status'  => true,
                'reviews' => [],
                'disputes' => [],
            ]);
        }

        # получаем отзывы по указанным параметрам
        $reviews = Review::query()
            # треба в масив даних по відгуку додати статус відгуку, статусів може бути 2: visible, hidden
            # visible - тоді, коли на заказ вже є два відгуки, тобто, якшо на один і той самий заказ залишили відгук і замовник і виконавець.
            # hidden - тільки якшо відгук тільки 1. якшо наприклад виконавець лишив відгук, а замовник ще не лишив.
            ->selectRaw('reviews.*, (SELECT IF(COUNT(r.id) = 2, "visible", "hidden") FROM reviews r WHERE r.rate_id = reviews.rate_id) AS status')
            ->with([
                'author:' . implode(',', User::FIELDS_FOR_SHOW),
                'recipient:' . implode(',', User::FIELDS_FOR_SHOW),
                'rate',
                'rate.order',
                'rate.disputes',
                'rate.disputes.problem',
            ])
            ->when($request->filled('order_id'), function ($query) use ($request) {
                return $query->whereHas('rate', function ($q) use ($request) {
                    $q->where('order_id', $request->get('order_id'));
                });
              })
            ->when($request->filled('author_id'), function ($query) use ($request) {
                return $query->where('user_id', $request->get('author_id'));
            })
            ->when($request->filled('recipient_id'), function ($query) use ($request) {
                return $query->where('recipient_id', $request->get('recipient_id'));
            })
            ->when($request->filled('recipient_type'), function ($query) use ($request) {
                return $query->where('recipient_type', $request->get('recipient_type'));
            })
            # если не указан ни один параметр, то отбираем все отзывы, где авторизированный пользователь был автором или получателем
            ->when($not_params, function ($query) use ($data) {
                return $query->where('user_id', $data['user_id'])->orWhere('recipient_id', $data['user_id']);
            })
            ->orderBy('id', $request->filled('sorting') ? $request->get('sorting') : 'asc')
            ->get();

        # получаем диспуты
        $disputes = Dispute::query()
            ->with('problem')
            ->when($request->filled('order_id'), function ($query) use ($request) {
                return $query->whereHas('rate', function ($q) use ($request) {
                    $q->where('order_id', $request->get('order_id'));
                });
            })
            ->when($request->filled('author_id'), function ($query) use ($request) {
                return $query->where('user_id', $request->get('author_id'));
            })
            ->get();

        # если указан код заказа или не указан ни один параметр, то группируем отзывы по типу получателя
        if ($request->filled('order_id') || $not_params) {
            $reviews = $reviews->keyBy('recipient_type');
        }

        return response()->json([
            'status'   => true,
            'reviews'  => null_to_blank($reviews),
            'disputes' => null_to_blank($disputes),
            'sql'=>getSQLForFixDatabase()
        ]);
    }

    /**
     * Получить пересчитанные рейтинги по пользователю в разрезе Заказчика (customer) и Исполнителя (performer).
     *
     * @param int $recipient_id
     * @return JsonResponse
     * {
     *     "status": true,
     *     "ratings": {
     *         "customer": {
     *             "count": 3,
     *             "scores": "13",
     *             "rating": "4.3"
     *         },
     *         "performer": {
     *             "count": 2,
     *             "scores": "7",
     *             "rating": "3.5"
     *         }
     *     }
     * }
     */
    public function getCalcRating(int $recipient_id): JsonResponse
    {
        $zero_ratings = [
            'customer' => [
                'count'  => 0,
                'scores' => 0,
                'rating' => 0,
            ],
            'performer' => [
                'count'  => 0,
                'scores' => 0,
                'rating' => 0,
            ]
        ];

        $selects = [
            'recipient_type',
            'COUNT(id) AS count',
            'SUM(scores) AS scores',
            'ROUND(SUM(scores) / COUNT(id), 1) AS rating',
        ];

        $ratings = Review::where('recipient_id', $recipient_id)
            ->selectRaw(implode(', ', $selects))
            ->groupBy('recipient_type')
            ->get()
            ->keyBy('recipient_type')
            ->makeHidden('recipient_type')
            ->toArray();

        return response()->json([
            'status'  => true,
            'ratings' => collect($zero_ratings)->merge($ratings)->all(),
        ]);
    }
}
