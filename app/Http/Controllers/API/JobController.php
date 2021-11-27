<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Modules\Liqpay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /**
     * Сформировать параметры для Liqpay-платежа.
     *
     * @param int $job_id
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function createLiqpayParams(int $job_id, Request $request): JsonResponse
    {
        app()->setLocale('ru');

        $job = Job::whereJobId($job_id)
            ->with('rate.order', 'rate.route')
            ->first();

        if (!$job) throw new ErrorException(__('message.job_not_found'));

        $user = $request->user();
        $user_name = trim($user->user_surname . ' ' . $user->user_name);

        $params = Liqpay::create_params(
            $user->user_id ?? 0,
            $user_name ?? '',
            $job_id,
            5,
            'UAH',
            'Test payment, route ' . route('api.liqpay.result'),
        );

        return response()->json([
            'status' => true,
            'result' => $params,
        ]);
    }

    /**
     * Получить результат оплаты от Liqpay.
     *
     * @param Request $request
     * @return void
     */
    public function resultLiqpay(Request $request)
    {
        Log::error($request->all());
    }
}
