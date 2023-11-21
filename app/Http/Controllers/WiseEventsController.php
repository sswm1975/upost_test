<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WiseEventsController extends Controller
{
    /**
     * Transfer state change event (відстеження успішних транзакцій).
     * @link https://docs.wise.com/api-docs/features/webhooks-notifications/event-types#transfer-state-change
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function eventTransferStateChange(Request $request)
    {
        Log::channel('daily')->debug('EVENT TRANSFER STATE CHANGE =>', $request->all());

        return response()->json(['status' => true]);
    }

    /**
     * Transfer payout failure events (відстеження неуспішних транзакцій).
     * @link https://docs.wise.com/api-docs/features/webhooks-notifications/event-types#transfer-payout-failure
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function eventTransferPayoutFailure(Request $request)
    {
        Log::channel('daily')->debug('EVENT TRANSFER PAYOUT FAILURE =>', $request->all());

        return response()->json(['status' => true]);
    }
}
