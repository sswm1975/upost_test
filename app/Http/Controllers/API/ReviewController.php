<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Добавить отзыв.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function addReview(Request $request): JsonResponse
    {
/*
        $user_id = $request->user()->id;

        $data = validateOrExit([
            'job_id'  => 'required|integer|exists:jobs,id|unique:reviews,job_id',
            'rating'  => 'required|integer',
            'comment' => 'required|string|max:300',
        ]);

        $job = Job::find($data['job_id']);

        # Отзыв можно оставлять только, если задача выполнена
        if ($job->status !== Job::STATUS_DONE) {
            throw new ErrorException(__('message.review_not_ready'));
        }

        $rate = $job->rate;
        $order = $rate->order;
        $route = $rate->route;

        $creator_id = $order->user_id;
        $freelancer_id = $route->user_id;

        # отзыв может оставлять только владелец заказа или маршрута
        if (!in_array($user_id, [$creator_id, $freelancer_id])) {
            throw new ErrorException(__('message.review_not_allowed'));
        }

        $review = new Review;
        $review->user_id = $user_id;
        $review->job_id = $data['job_id'];
        $review->rating = $data['rating'];
        $review->comment = htmlentities($data['comment']);

        if ($user_id == $creator_id) {
            # Отзыв оставляет Заказчик на маршрут, тогда Исполнителю увеличиваем рейтинг
            User::find($freelancer_id)->increment('freelancer_rating', $data['rating']);

            $review->to_user_id = $freelancer_id;
            $review->type = Review::TYPE_CREATOR;
            $model = $route;
        } else {
            # Отзыв оставляет Исполнитель на Заказ, тогда Заказчику увеличиваем рейтинг
            User::find($creator_id)->increment('creator_rating', $data['rating']);

            $review->to_user_id = $creator_id;
            $review->type = Review::TYPE_FREELANCER;
            $model = $order;
        }

        $model->review()->save($review);

        return response()->json([
            'status' => true,
            'sql' => getSQLForFixDatabase()
        ]);
*/
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
