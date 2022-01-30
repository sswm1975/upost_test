<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Exceptions\ValidatorException;
use App\Models\Dispute;

class DisputeController extends Controller
{
    /**
     * Добавить спор на задание.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function addDispute(Request $request): JsonResponse
    {
/*
        $data = validateOrExit([
            'job_id'  => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $is_permission = Job::query()
                        ->where('job_id', $value)
                        ->where('job_status',Job::STATUS_WORK)
                        ->whereHas('rate', function ($qr) {
                            return $qr->whereHas('order', function($qo) {
                                return $qo->where('user_id', request()->user()->user_id);
                            });
                        })->count();

                    if(!$is_permission) {
                        return $fail(__('message.not_have_permission'));
                    }
                }
            ],
            'problem_id'  => 'required|integer',
            'files' => 'required|array|min:1',
            'comment' => 'required|string|max:300',
        ]);

        Dispute::create($data);

        $job = Job::find($request->get('job_id'));
        $job->update(['job_status' => Job::STATUS_DISPUTE]);

        Rate::find($job->rate_id)->update(['rate_status' => Rate::STATUS_DISPUTE]);

        return response()->json([
            'status' => true,
        ]);
*/
    }
}
