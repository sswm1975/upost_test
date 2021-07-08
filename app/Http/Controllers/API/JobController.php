<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JobController extends Controller
{
    /**
     * Создать задание.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function addJob(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'rate_id' => 'required|integer|owner_rate|unique:jobs,rate_id',
        ]);

        $job = Job::create([
            'rate_id' => $data['rate_id'],
            'status'  => Job::STATUS_ACTIVE,
        ])->toArray();

        return response()->json([
            'status' => true,
            'result' => null_to_blank($job),
        ]);
    }

    /**
     * Подтверждение правильности покупки (заказчик).
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function acceptJob(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'rate_id' => 'required|integer|owner_rate|exists:jobs,rate_id',
        ]);

        Job::whereRateId($data['rate_id'])->update(['job_status' => Job::STATUS_WORK]);

        return response()->json(['status' => true]);
    }
}
