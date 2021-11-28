<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Transaction;
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
            'Test payment',
        );

        return response()->json([
            'status' => true,
            'result' => $params,
        ]);
    }

    /**
     * Обработать результат оплаты от Liqpay и сохранение транзакции.
     * (описание см. https://www.liqpay.ua/documentation/api/callback)
     *
     * @param Request $request
     * @return void
     */
    public function callbackLiqpay(Request $request)
    {
        $data = $request->get('data');
        $signature = $request->get('signature');

        if (empty($data) || empty($signature) ) {
            return response()->json([
                'status' => false,
                'error'  => 'Нет данных в data и/или в signature'
            ]);
        }

        $responce = Liqpay::decode_responce($data, $signature);
        if (!$responce['status']) {
            return response()->json($responce);
        }

        $liqpay = $responce['data'];

        Log::info($liqpay);

        if (!in_array($liqpay['status'], ['success', 'sandbox'])) {
            return response()->json([
                'status' => false,
                'error'  => 'Статус платежа не равен "success" или "sandbox", получен статус "'.$liqpay['status'].'"',
            ]);
        }

        Transaction::create([
            'user_id'     => $liqpay['info']['user_id'],
            'job_id'      => $liqpay['info']['job_id'],
            'amount'      => $liqpay['amount'],
            'description' => $liqpay['description'],
            'status'      => $liqpay['status'],
            'response'    => $liqpay,
            'payed_at'    => gmdate('Y-m-d H:i:s', strtotime("+2 hours", $liqpay['end_date'] / 1000)),
        ]);

        return response()->json([
            'status' => true,
            'data'   => $liqpay,
        ]);
    }
}
