<?php

namespace App\Http\Controllers\API;

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
     */
    public function createJob(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'rate_id' => 'required|integer|unique:jobs,rate_id',
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $user_id = $request->user()->user_id;

        $rate = Rate::query()
            ->with('route:route_id,user_id', 'order:order_id,user_id')
            ->where('rate_id', $request->rate_id)
            ->first();

        if (empty($rate)) {
            return response()->json([
                'status' => 404,
                'errors' => 'rate_not_found',
            ]);
        }

        # запрещено создавать задание, если пользователь к этой ставке не имеет отношения
        if ($rate->order->user_id <> $user_id && $rate->route->user_id <> $user_id) {
            return response()->json([
                'status' => 404,
                'errors' => 'rate_is_not_yours',
            ]);
        }

        $job = Job::create([
            'rate_id' => $request->rate_id,
            'status'  => 'active',
        ]);

        return response()->json([
            'status' => 200,
            'result' => $job,
        ]);
    }

}
