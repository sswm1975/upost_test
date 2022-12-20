<?php

namespace App\Platform\Actions\Dispute;

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
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CloseDisputeGuiltyCustomer extends RowAction
{
    public $name = 'Закрыть спор (виноват заказчик)';
    protected $selector = '.text-green';

    public function authorize(Administrator $user, Dispute $model): bool
    {
        return $user->isAdministrator() || $user->id == $model->admin_user_id;
    }

    public function form()
    {
        $this->select('dispute_closed_reason_id', 'Причина')
            ->placeholder('Выберите причину закрытия')
            ->options(DisputeClosedReason::whereGuilty('customer')->pluck('name', 'id'))
            ->rules('required');

        $this->textarea('reason_closing_description', 'Детальное описание закрытия спора')
            ->placeholder('Введите детальное описание причины закрытия спора')
            ->rules('required');
    }

    public function handle(Dispute $model, Request $request): Response
    {
        $dispute_closed_reason_id = $request->get('dispute_closed_reason_id');
        $reason_closing_description = $request->get('reason_closing_description');

        $dispute_closed_reason_alias = DisputeClosedReason::find($dispute_closed_reason_id, ['alias'])->alias;

        $exists_verified_track = $model->track()->where('status', Track::STATUS_VERIFIED)->exists();
        if (! $exists_verified_track) {
            return $this->response()->error("По спору с кодом {$model->id} нет ТТН или посылка ещё не получена!");
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

            # трек
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
            Chat::addSystemMessage($model->chat_id, [$dispute_closed_reason_alias, 'dispute_close_info_option2'], [
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
            return $this->response()->error($e->getMessage());
        }

        return $this->response()
            ->success('Спор закрыт!')
            ->refresh();
    }
}
