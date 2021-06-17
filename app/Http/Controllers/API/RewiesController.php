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

class RewiesController extends Controller
{
    /**
     * Добавить отзыв.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|TryException
     * @throws ErrorException
     */
    public function addReview(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        $validator = Validator::make($request->all(),
            [
                //    'user_id' => 'required|integer|exists:users,user_id',
                'job_id'  => [
                    'required',
                    'integer',
                    'exists:jobs,job_id',
                    Rule::unique('reviews', 'job_id')->where(function ($query) use ($userId) {
                        return $query->where('user_id', $userId);
                    }),
                ],
                'rating'  => 'required|integer',
                'comment' => 'required|string|max:300',
            ],
            ['unique' => __('message.unique_review')]
        );
        $this->returnValidated($validator);

        $data = $request->post();
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
            if ($userId === $creatorId) {
                $user = User::find($freelancerId);
                $user->user_freelancer_rating += $data['rating'];
            }
            else {
                $user = User::find($creatorId);
                $user->user_creater_rating += $data['rating'];
            }
            $user->save();

            Review::create([
                'user_id' => $userId,
                'job_id'  => $data['job_id'],
                'rating'  => $data['rating'],
                'comment' => htmlentities($data['comment']),
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
     * @throws ValidatorException
     */
    public function showReviews(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            ['user_id' => 'required|integer|exists:users,user_id']
        );
        $this->returnValidated($validator);

        $jobs = Job::whereHas('rate', function ($q) use ($request) {
            return $q->where(['user_id' => $request->get('user_id')]);
        })->get()->pluck('job_id');

        $reviews = Review::whereIn('job_id', $jobs)->get()->toArray();


        return response()->json([
            'status' => true,
            'number' => count($reviews),
            'result' => null_to_blank($reviews),
        ]);
    }

    public function getRating(Request $request )
    {
        $validator = Validator::make($request->all(),
            ['user_id' => 'required|integer|exists:users,user_id']
        );
        $this->returnValidated($validator);

    }
}
