<?php

namespace App\Platform\Actions\Dispute;

use App\Models\Chat;
use App\Models\Dispute;
use App\Models\DisputeClosedReason;
use App\Models\Payment;
use App\Models\Rate;
use App\Models\Transaction;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CloseDisputeGuiltyPerformer extends BatchAction
{
    public $name = 'Закрыть спор (виноват путешественник)';
    protected $selector = '.text-blue';

    /**
     * Закрыть спор может или Администратор или закрепленный Менеджер по спорам.
     *
     * @param Administrator $user
     * @param Collection $model
     * @return bool
     */
    public function authorize(Administrator $user, Collection $model)
    {
        return $user->isAdministrator() || $user->id == $model->admin_user_id;
    }

    public function form()
    {
        $this->select('dispute_closed_reason_id', 'Причина')
            ->placeholder('Выберите причину закрытия')
            ->options(DisputeClosedReason::whereGuilty('performer')->pluck('name', 'id'))
            ->rules('required');

        $this->textarea('reason_closing_description', 'Детальное описание закрытия спора')
            ->placeholder('Введите детальное описание причины закрытия спора')
            ->rules('required');
    }

    public function handle(Collection $collection, Request $request)
    {
        $dispute_closed_reason_id = $request->get('dispute_closed_reason_id');
        $reason_closing_description = $request->get('reason_closing_description');

        $alias = DisputeClosedReason::find($dispute_closed_reason_id, ['alias'])->alias;

        foreach ($collection as $model) {
            DB::beginTransaction();

            try {
                # спору меняем статус на Закрытый, сохраняем код причины закрытия и комментарий менеджера по закрытию
                $model->status = Dispute::STATUS_CLOSED;
                $model->dispute_closed_reason_id = $dispute_closed_reason_id;
                $model->reason_closing_description = $reason_closing_description;
                $model->save();

                # статус Ставки меняем на Неудачный
                $model->rate()->update(['status' => Rate::STATUS_FAILED]);

                # статус Заказа меняем на Успешный
                $model->rate->order()->update(['status' => Rate::STATUS_SUCCESSFUL]);

                # путешественнику увеличиваем счетчик "Количество неудачных доставок"
                $model->rate->user()->increment('failed_delivery_count');

                # создаем заявку на возврат средств Заказчику
                $transaction = Transaction::firstWhere('rate_id', '=', $model->rate->id);
                Payment::create([
                    'user_id'     => $model->rate->order->user_id,
                    'amount'      => $transaction->amount - $transaction->service_fee,
                    'description' => 'Возмещение средств заказчику по спору №' . $model->id,
                ]);

                # информируем в чат причину закрытия спора, а также отсылаем ссылку на информацию, что делать дальше
                # меняем статус чата на закрытый и блокируем на добавление новых сообщений
                Chat::addSystemMessage($model->chat_id, [$alias, 'dispute_close_info_option1'], [
                    'status'      => Chat::STATUS_CLOSED,
                    'lock_status' => Chat::LOCK_STATUS_ADD_MESSAGE_LOCK_ALL,
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                $this->response()->error($e->getMessage());
            }
        }

        return $this->response()->success('Выполнено!')->refresh();
    }
}
