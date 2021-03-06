<?php

namespace App\Platform\Actions\Payment;

use App\Models\Payment;
use Encore\Admin\Actions\Response;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Auth\Database\Administrator;

class RejectPayment extends RowAction
{
    public $name = 'Отклонить заявку';
    protected $selector = '.text-red';

    public function authorize(Administrator $user, Payment $model): bool
    {
        return $user->inRoles(['administrator', 'finance_manager'])
            && in_array($model->status, [Payment::STATUS_NEW, Payment::STATUS_APPOINTED]);
    }

    public function dialog()
    {
        $this->confirm('Вы точно хотите отклонить заявку?');
    }

    public function handle(Payment $model): Response
    {
        $model->status = Payment::STATUS_REJECTED;
        $model->save();

        return $this->response()->success('Заявка отклонена')->refresh();
    }
}
