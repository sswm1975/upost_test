<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Chat;
use App\Models\Dispute;
use App\Models\DisputeClosedReason;
use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Rate;
use App\Models\Track;
use App\Models\Transaction;
use Encore\Admin\Actions\Response;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Layout\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisputeController extends BaseController
{
    protected string $title = 'Споры';
    protected string $icon = 'fa-gavel';
    protected string $entity = 'disputes';
    protected int $count_columns = 14;

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $statuses = array_merge(Dispute::STATUSES, ['all' =>  'Все']);

        return compact('statuses');
    }

    public function index(Content $content): Content
    {
        $content = parent::index($content);

        $users = Administrator::whereNotNull('user_id')->pluck('username', 'id');
        $closed_reasons =[
            'guilty_performer' => DisputeClosedReason::whereGuilty('performer')->pluck('name', 'id'),
            'guilty_customer' => DisputeClosedReason::whereGuilty('customer')->pluck('name', 'id'),
        ];
        $content->row(view('platform.datatables.disputes.modals', compact('users', 'closed_reasons')));

        return $content;
    }

    /**
     * Получить данные для таблицы.
     *
     * @return array
     */
    public function getData()
    {
        $status = request('status', Dispute::STATUS_ACTIVE);

        # отбираем маршруты
        $data = Dispute::with(['problem', 'user', 'respondent', 'admin_user'])
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get()
            ->map(function ($row) use ($status) {
                return [
                    'id' => $row->id,
                    'status' => $row->status,
                    'problem_id' => $row->problem_id,
                    'problem_name' => $row->problem->name,
                    'problem_days' => $row->problem->days,
                    'manager_id' => $row->admin_user->id ?? '',
                    'manager_full_name' => $row->admin_user->full_name ?? '',
                    'user_id' => $row->user->id,
                    'user_full_name' => $row->user->full_name,
                    'respondent_id' => $row->respondent->id,
                    'respondent_full_name' => $row->respondent->full_name,
                    'deadline' => $row->deadline->format('d.m.Y'),
                    'created_at' => $row->created_at->format('d.m.Y'),
                    'updated_at' => !empty($row->updated_at) ? $row->updated_at->format('d.m.Y') : '',
                ];
            })
            ->all();

        return compact('data');
    }

    /**
     * Назначить спор(ы) менеджеру.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function appointDispute(Request $request): JsonResponse
    {
        if (!$request->filled(['ids', 'admin_user_id'])) {
            return static::jsonResponse('Не заполнены обязательные параметры!', false);
        }

        $ids = json_decode($request->get('ids'));
        Dispute::whereKey($ids)->update([
            'admin_user_id' => $request->get('admin_user_id'),
            'status' => Dispute::STATUS_APPOINTED,
        ]);

        return static::jsonResponse('Отмеченные споры назначены менеджеру!');
    }

    /**
     * Взять спор(ы) в работу.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function inWorkDispute(Request $request): JsonResponse
    {
        if (!$request->filled(['ids'])) {
            return static::jsonResponse('Не заполнены обязательные параметры!', false);
        }

        $ids = json_decode($request->get('ids'));
        Dispute::whereKey($ids)->update([
            'status' => Dispute::STATUS_IN_WORK,
        ]);

        return static::jsonResponse('Споры взяты в работу!');
    }

    /**
     * Отменить спор(ы).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function canceledDispute(Request $request): JsonResponse
    {
        if (!$request->filled(['ids'])) {
            return static::jsonResponse('Не заполнены обязательные параметры!', false);
        }

        $ids = json_decode($request->get('ids'));
        Dispute::whereKey($ids)->update([
            'status' => Dispute::STATUS_CANCELED,
        ]);

        return static::jsonResponse('Споры отменены!');
    }

    /**
     * Закрыть спор (виноват путешественник).
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function closeDisputeGuiltyPerformer(Request $request): JsonResponse
    {
        if (!$request->filled(['ids', 'dispute_closed_reason_id', 'reason_closing_description'])) {
            return static::jsonResponse('Не заполнены обязательные параметры!', false);
        }

        $dispute_closed_reason_id = $request->get('dispute_closed_reason_id');
        $reason_closing_description = $request->get('reason_closing_description');

        $alias = DisputeClosedReason::find($dispute_closed_reason_id, ['alias'])->alias;

        $ids = json_decode($request->get('ids'));
        $disputes = Dispute::findMany($ids);

        foreach ($disputes as $model) {
            DB::beginTransaction();

            try {
                # спору меняем статус на Закрытый, сохраняем код причины закрытия и комментарий менеджера по закрытию
                $model->status = Dispute::STATUS_CLOSED;
                $model->dispute_closed_reason_id = $dispute_closed_reason_id;
                $model->reason_closing_description = $reason_closing_description;
                $model->save();

                # статус Ставки меняем на Неудачный
                $model->rate()->update(['status' => Rate::STATUS_FAILED]);

                # статус Заказа меняем на Неудачный
                $model->rate->order()->update(['status' => Order::STATUS_FAILED]);

                # путешественнику увеличиваем счетчик "Количество неудачных доставок"
                $model->rate->user()->increment('failed_delivery_count');

                # создаем заявку на возврат средств Заказчику
                $transaction = Transaction::firstWhere('rate_id', '=', $model->rate->id);
                Payment::create(['user_id' => $model->rate->order->user_id, 'rate_id' => $model->rate->id, 'order_id' => $model->rate->order_id, 'amount' => $transaction->amount - $transaction->service_fee, 'type' => Payment::TYPE_REFUND, 'description' => 'Возмещение средств заказчику по спору №' . $model->id,]);

                # информируем в чат причину закрытия спора, а также отсылаем ссылку на информацию, что делать дальше
                # меняем статус чата на закрытый и блокируем на добавление новых сообщений
                Chat::addSystemMessage($model->chat_id, [$alias, 'dispute_close_info_option1'], ['status' => Chat::STATUS_CLOSED, 'lock_status' => Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL,]);

                # создаем уведомление "Закрыт спор"
                if (active_notice_type($notice_type = NoticeType::DISPUTE_CLOSED)) {
                    Notice::create(['user_id' => $model->rate->order->user_id, 'notice_type' => $notice_type, 'object_id' => $model->rate->order_id, 'data' => ['order_name' => $model->rate->order->name, 'rate_id' => $model->rate->id],]);
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollback();
                return static::jsonResponse($e->getMessage(), false);
            }
        }

        return static::jsonResponse('Диспуты закрыты!');
    }

    /**
     * Закрыть спор (виноват заказчик).
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function closeDisputeGuiltyCustomer(Request $request): JsonResponse
    {
        if (!$request->filled(['ids', 'dispute_closed_reason_id', 'reason_closing_description'])) {
            return static::jsonResponse('Не заполнены обязательные параметры!', false);
        }
        $dispute_closed_reason_id = $request->get('dispute_closed_reason_id');
        $reason_closing_description = $request->get('reason_closing_description');

        $alias = DisputeClosedReason::find($dispute_closed_reason_id, ['alias'])->alias;

        $ids = json_decode($request->get('ids'));
        $disputes = Dispute::findMany($ids);

        foreach ($disputes as $model) {
            $exists_verified_track = $model->track()->where('status', Track::STATUS_VERIFIED)->exists();
            if (! $exists_verified_track) {
                return static::jsonResponse("По спору с кодом {$model->id} нет ТТН или посылка ещё не получена!", false);
            }

            DB::beginTransaction();

            try {
                # спору меняем статус на Закрытый, сохраняем код причины закрытия и комментарий менеджера по закрытию
                $model->status = Dispute::STATUS_CLOSED;
                $model->dispute_closed_reason_id = $dispute_closed_reason_id;
                $model->reason_closing_description = $reason_closing_description;
                $model->save();

                # статус Ставки меняем на Неудачный
                $model->rate()->update(['status' => Rate::STATUS_FAILED]);

                # статус Заказа меняем на Неудачный
                $model->rate->order()->update(['status' => Order::STATUS_FAILED]);

                # статус Трека меняем на Закрытый
                $model->track()->update(['status' => Track::STATUS_CLOSED]);

                # заказчику увеличиваем счетчик "Количество неудачных получений"
                $model->rate->order->user()->increment('failed_receive_count');

                # создаем заявку на возврат средств Заказчику
                $transaction = Transaction::firstWhere('rate_id', '=', $model->rate->id);
                Payment::create([
                    'user_id'     => $model->rate->user_id,
                    'rate_id'     => $model->rate->id,
                    'order_id'    => $model->rate->order_id,
                    'amount'      => $transaction->amount - $transaction->liqpay_fee - $transaction->service_fee,
                    'type'        => Payment::TYPE_REFUND,
                    'description' => 'Возмещение средств путешественнику по спору №' . $model->id,
                ]);

                # информируем в чат причину закрытия спора, а также отсылаем ссылку на информацию, что делать дальше
                # меняем статус чата на закрытый и блокируем на добавление новых сообщений
                Chat::addSystemMessage($model->chat_id, [$alias, 'dispute_close_info_option2'], [
                    'status'      => Chat::STATUS_CLOSED,
                    'lock_status' => Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL,
                ]);

                # создаем уведомление "Закрыт спор"
                if (active_notice_type($notice_type = NoticeType::DISPUTE_CLOSED)) {
                    Notice::create([
                        'user_id'     => $model->rate->user_id,
                        'notice_type' => $notice_type,
                        'object_id'   => $model->rate->order_id,
                        'data'        => ['order_name' => $model->rate->order->name, 'rate_id' => $model->rate->id],
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return static::jsonResponse($e->getMessage(), false);
            }
        }

        return static::jsonResponse('Диспуты закрыты!');
    }

    /**
     * Сформировать JSON-ответ
     *
     * @param string $message
     * @param bool $status
     * @return JsonResponse
     */
    private static function jsonResponse(string $message, bool $status = true): JsonResponse
    {
        return response()->json(
            compact('status', 'message'),
            200,
            ['Content-Type' => 'application/json; charset=utf-8'],
            JSON_UNESCAPED_UNICODE
        );
    }
}
