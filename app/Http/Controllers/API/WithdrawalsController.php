<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalsController extends Controller
{
    # Минимальная суммы для вывода денег
    const MIN_WITHDRAWAL_AMOUNT = 10;

    public function add(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->wallet < self::MIN_WITHDRAWAL_AMOUNT) {
            throw new ErrorException(__('message.wallet.not_enough_funds'));
        }

        if (Withdrawal::existsUnfinished()) {
            throw new ErrorException(__('message.wallet.exists_unfinished_withdrawals'));
        }

        $withdrawal = Withdrawal::create();

        return response()->json([
            'status'     => true,
            'withdrawal' => $withdrawal,
        ]);
    }
}
