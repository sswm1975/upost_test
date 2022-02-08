<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
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

        # авторизированный пользователь должен быть владельцем заказа или маршрута, а ставка быть в статусе выполнена
        $rate = Rate::whereKey($data['rate_id'])
            ->whereStatus(Rate::STATUS_DONE)
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
                'scores_count'  => DB::raw("scores_count + {$data['rating']}"),
            ]);

        return response()->json([
            'status' => true,
            'rate'=>$rate,
            'sql' => getSQLForFixDatabase()
        ]);
    }

    /**
     * Получить список отзывов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function showReviews(Request $request): JsonResponse
    {
/*
        $filters = validateOrExit([
            'user_id'  => 'required|integer|exists:users,id',
            'type'     => 'sometimes|required|in:creator,freelancer',
        ]);

        $jobs = Job::whereHas('rate', function ($q) use ($filters) {
            return $q->whereUserId($filters['user_id']);
        })->get()->pluck('job_id');

        $reviews = Review::whereIn('job_id', $jobs)
            ->when(isset($filters['type']), function ($q) use ($filters) {
                return $q->whereType($filters['type']);
            })
            ->get();

        return response()->json([
            'status' => true,
            'number' => count($reviews),
            'result' => null_to_blank($reviews),
        ]);
*/
    }

    /**
     * @throws ValidatorException|ValidationException
     */
    public function getRating(Request $request)
    {
        $data = validateOrExit(['user_id' => 'required|integer|exists:users,id']);

        $user = User::find($data['user_id']);

        $creatorReviews = Review::where(['user_id' => $user->id, 'type' => Review::TYPE_CREATOR])->count();
        $freelancerReviews = Review::where(['user_id' => $user->id, 'type' => Review::TYPE_FREELANCER])->count();

        $creatorRating = $creatorReviews ? $user->user_creator_rating / $creatorReviews : 0;
        $freelancerRating = $freelancerReviews ? $user->user_freelancer_rating / $freelancerReviews : 0;

        return response()->json([
            'status'            => true,
            'creator_rating'    => round($creatorRating, 1),
            'freelancer_rating' => round($freelancerRating, 1),
        ]);
    }
}
