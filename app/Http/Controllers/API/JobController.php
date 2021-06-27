<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Rate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    /**
     * Создать задание.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     * @throws ErrorException
     */
    public function addJob(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'rate_id' => 'required|integer|unique:jobs,rate_id',
            ]
        );

        validateOrExit($validator);

        $user_id = $request->user()->user_id;

        $rate = Rate::query()
            ->with('route:route_id,user_id', 'order:order_id,user_id')
            ->where('rate_id', $request->rate_id)
            ->first();

        if (empty($rate)) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        # запрещено создавать задание, если пользователь к этой ставке не имеет отношения
        if ($rate->order->user_id <> $user_id && $rate->route->user_id <> $user_id) {
            throw new ErrorException(__('message.rate_not_found'));
        }

        $job = Job::create([
            'rate_id' => $request->rate_id,
            'status'  => Job::STATUS_ACTIVE,
        ]);

        return response()->json([
            'status' => true,
            'result' => null_to_blank($job->toArray()),
        ]);
    }
}
