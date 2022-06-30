<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurseController extends Controller
{
    /**
     * Получить список транзакций/заявок на выплату по пользователю.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function show(Request $request): JsonResponse
    {
        $user_id = $request->user()->id ?? 0;

        if (empty($user_id)) {
            throw new ErrorException(__('message.error'));
        }

        $transactions = Transaction::query()
            ->selectRaw('"payment" AS `type`, transactions.payed_at, -1 * transactions.amount AS amount, orders.`name` AS order_name, transactions.`status`')
            ->join('rates', 'rates.id', 'transactions.rate_id')
            ->join('orders', 'orders.id', 'rates.order_id')
            ->where('transactions.user_id', $user_id)
            ->get()
            ->toArray();

        $payments = Payment::query()
            ->selectRaw('payments.`type`, IFNULL(payments.updated_at, payments.created_at) AS payed_at, payments.amount, orders.`name` AS order_name, payments.`status`')
            ->join('orders', 'orders.id', 'payments.order_id')
            ->where('payments.user_id', 2)
            ->get()
            ->toArray();

        $transactions = collect($transactions)->merge($payments)->sortBy('payed_at')->toArray();

        return response()->json([
            'status'       => true,
            'transactions' => null_to_blank($transactions),
        ]);
    }
}
