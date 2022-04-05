<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Problem;
use App\Models\Rate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use App\Exceptions\ValidatorException;
use App\Models\Dispute;

class DisputeController extends Controller
{
    /**
     * Правила проверки входных данных запроса при сохранении заказа.
     *
     * @return array
     * @throws ValidationException|ValidatorException
     */
    protected static function rules4saveDispute(): array
    {
        return validateOrExit([
            'problem_id'  => 'required|integer|exists:problems,id',
            'rate_id'  => [
                'required',
                'integer',
                function ($attribute, $rate_id, $fail) {
                    $is_permission = Rate::whereKey($rate_id)
                        ->where('chat_id', request('chat_id', 0))
                        ->count();

                    if (! $is_permission) {
                        return $fail(__('message.not_have_permission'));
                    }

                    if (Dispute::whereRateId($rate_id)->count()) {
                        return $fail(__('message.dispute_exists'));
                    }
                }
            ],
            'chat_id'  => [
                'required',
                'integer',
                function ($attribute, $chat_id, $fail) {
                    $is_permission = Chat::whereKey($chat_id)
                        ->where(function($query) {
                            $user_id = request()->user()->id;
                            $query->where('performer_id', $user_id)->orWhere('customer_id', $user_id);
                        })
                        ->count();

                    if (! $is_permission) {
                        return $fail(__('message.not_have_permission'));
                    }
                }
            ],
            'text'     => 'required|string|censor',
            'images'   => 'nullable|array|max:8',
            'images.*' => 'nullable|string',
        ]);
    }

    /**
     * Добавить спор.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function create(Request $request): JsonResponse
    {
        $data = $this->rules4saveDispute();

        $days = Problem::find($data['problem_id'])->value('days');
        $data['deadline'] = Carbon::now()->addDays($days)->toDateString();

        $message = Message::create(Arr::only($data, ['chat_id', 'text', 'images', 'user_id']));
        $data['message_id'] = $message->id;

        Dispute::create($data);

        Rate::find($data['rate_id'])->update(['status' => Rate::STATUS_DISPUTE]);

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Изменить статус спора.
     *
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function changeStatus(int $id): JsonResponse
    {
        $data = validateOrExit([
            'status' => 'required|in:' . implode(',', Dispute::STATUSES),
        ]);

        $affected_rows = Dispute::whereKey($id)->update($data);

        return response()->json(['status' => $affected_rows > 0]);
    }

    /**
     * Изменить дату дедлайна спора.
     *
     * @param int $id
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function changeDeadline(int $id): JsonResponse
    {
        $data = validateOrExit([
            'deadline' => 'required|date_format:Y-m-d|after_or_equal:'.date('Y-m-d'),
        ]);

        $affected_rows = Dispute::whereKey($id)->update($data);

        return response()->json(['status' => $affected_rows > 0]);
    }
}
