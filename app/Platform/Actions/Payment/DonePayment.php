<?php

namespace App\Platform\Actions\Payment;

use App\Models\Payment;
use Encore\Admin\Actions\Response;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Auth\Database\Administrator;

class DonePayment extends RowAction
{
    public $name = 'Заявка выполнена';
    protected $selector = '.text-green';

    public function authorize(Administrator $user, Payment $model): bool
    {
        return $user->inRoles(['administrator', 'finance_manager'])
            && $model->status == Payment::STATUS_APPOINTED;
    }

    public function dialog()
    {
        $this->confirm('Заявка на выплату платежа обработана?');
    }

    public function handle(Payment $model): Response
    {
        $model->status = Payment::STATUS_DONE;
        $model->save();

        return $this->response()->success('Заявка выполнена!')->refresh();
    }
}
