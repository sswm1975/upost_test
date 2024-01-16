<?php

namespace App\Http\Controllers;

use App\Models\WiseEvent;
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

        $json = $request->all();
        $data = $json['data'] ?? null;
        $resource = $data['resource'] ?? null;
        if (empty($json) || empty($data) || empty($resource)) {
            return response()->json(['status' => true, 'error' => 'Event json empty']);
        }

        $state = $data['current_state'] ?? 'none';

        WiseEvent::create([
            'event_type'  => WiseEvent::EVENT_TYPE_TRANSFER_STATE_CHANGE,
            'transfer_id' => $resource['id'],
            'profile_id'  => $resource['profile_id'],
            'account_id'  => $resource['account_id'],
            'state'       => $state,
            'event'       => $json,
            'status'      => $state == WiseEvent::STATE_FOR_PROCESSING ? WiseEvent::STATUS_NEW : WiseEvent::STATUS_SKIPPED,
        ]);

        return response()->json(['status' => true, 'message' => 'Successful']);
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

        $json = $request->all();
        $data = $json['data'] ?? null;
        $resource = $data['resource'] ?? null;
        if (empty($json) || empty($data) || empty($resource)) {
            return response()->json(['status' => true, 'error' => 'Event json empty']);
        }

        $state = $data['current_state'] ?? 'none';

        WiseEvent::create([
            'event_type'  => WiseEvent::EVENT_TYPE_TRANSFER_PAYOUT_FAILURE,
            'transfer_id' => $resource['id'],
            'profile_id'  => $resource['profile_id'],
            'account_id'  => $resource['account_id'],
            'state'       => $state,
            'event'       => $json,
            'status'      => WiseEvent::STATUS_SKIPPED,
        ]);

        return response()->json(['status' => true, 'message' => 'Successful']);
    }
}
