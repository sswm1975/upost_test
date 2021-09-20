<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\TryException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Добавить отзыв.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     * @throws TryException
     * @throws ValidationException
     * @throws ValidatorException
     * @throws \Throwable
     */
    public function addReview(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = validateOrExit(
            [
                'job_id'  => [
                    'required',
                    'integer',
                    'exists:jobs,id',
                    Rule::unique('reviews', 'job_id')->where(function ($query) use ($userId) {
                        return $query->where('user_id', $userId);
                    }),
                ],
                'rating'  => 'required|integer',
                'comment' => 'required|string|max:300',
            ],
            ['unique' => __('message.unique_review')]
        );

        $job = Job::find($data['job_id']);

        if ($job->status !== Job::STATUS_DONE) {
            throw new ErrorException(__('message.review_not_ready'));
        }

        $rate = $job->rate;
        $order = $rate->order;
        $creatorId = $order->user_id;
        $freelancerId = $rate->user_id;

        if (!in_array($userId, [$creatorId, $freelancerId])) {
            throw new ErrorException(__('message.review_not_allowed'));
        }

        try {
            DB::beginTransaction();

            // Заказчик
            if ($userId === $creatorId) {
                $user = User::find($freelancerId);
                $user->user_freelancer_rating += $data['rating'];
                $type = Review::TYPE_CREATOR;
            }
            // Исполнитель
            else {
                $user = User::find($creatorId);
                $user->user_creator_rating += $data['rating'];
                $type = Review::TYPE_FREELANCER;
            }
            $user->save();

            Review::create([
                'user_id'     => $userId,
                'job_id'      => $data['job_id'],
                'rating'      => $data['rating'],
                'comment'     => htmlentities($data['comment']),
                'type'        => $type,
            ]);
            DB::commit();
            return response()->json([
                'status' => true,
            ]);
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw new TryException($e->getMessage());
        }
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
        $filters = validateOrExit([
            'user_id'  => 'required|integer|exists:users,id',
            'type'     => 'sometimes|required|in:creator,freelancer',
        ]);

        $jobs = Job::whereHas('rate', function ($q) use ($filters) {
            return $q->whereUserId($filters['user_id']);
        })->get()->pluck('job_id');

        $reviews = Review::whereIn('job_id', $jobs)
            ->when(isset($filters['type']), function ($q) use ($filters) {
                return $q->whereType(Review::TYPES[$filters['type']]);
            })
            ->get();

        return response()->json([
            'status' => true,
            'number' => count($reviews),
            'result' => null_to_blank($reviews),
        ]);
    }

    public function getRating(Request $request)
    {
        $validator = Validator::make($request->all(),
            ['user_id' => 'required|integer|exists:users,id']
        );
         validateOrExit($validator);

        $user = User::find($request->get('user_id'));

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
