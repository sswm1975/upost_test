<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurseController extends Controller
{
    /** @var int Количество платежей на странице */
    const DEFAULT_PER_PAGE = 5;

    /** @var string Дефолтная сортировка по дате добавления заказа */
    const DEFAULT_SORT_BY = 'payed_at';

    /** @var string Дефолтная сортировка по убыванию */
    const DEFAULT_SORTING = 'desc';

    /**
     * Получить список транзакций/заявок на выплату по пользователю.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function show(Request $request): JsonResponse
    {
        $user_id = $request->user()->id ?? 0;

        if (empty($user_id)) {
            throw new ErrorException(__('message.error'));
        }

        # проверяем входные данные
        $data = validateOrExit([
            'sorting'     => 'sometimes|required|in:asc,desc',
            'page-number' => 'sometimes|required|integer|min:1',
        ]);

        $payments = Payment::query()
            ->selectRaw('payments.`type`, IFNULL(payments.updated_at, payments.created_at) AS payed_at, payments.amount, orders.id AS order_id, orders.`name` AS order_name, payments.`status`')
            ->join('orders', 'orders.id', 'payments.order_id')
            ->where('payments.user_id', $user_id);

        $transactions = Transaction::query()
            ->selectRaw('"payment" AS `type`, transactions.payed_at, -1 * transactions.amount AS amount, orders.id AS order_id, orders.`name` AS order_name, transactions.`status`')
            ->join('rates', 'rates.id', 'transactions.rate_id')
            ->join('orders', 'orders.id', 'rates.order_id')
            ->where('transactions.user_id', $user_id)
            ->union($payments)
            ->orderBy(self::DEFAULT_SORT_BY, $data['sorting'] ?? self::DEFAULT_SORTING)
            ->paginate(self::DEFAULT_PER_PAGE, ['*'], 'page', $data['page-number'] ?? 1)
            ->toArray();

        return response()->json([
            'status'       => true,
            'transactions' => null_to_blank($transactions['data']),
            'count'        => $transactions['total'],
            'page'         => $transactions['current_page'],
            'pages'        => $transactions['last_page'],
        ]);
    }
}
