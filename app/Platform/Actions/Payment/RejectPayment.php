<?php

namespace App\Platform\Actions\Payment;

use App\Models\Payment;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class RejectPayment extends BatchAction
{
    public $name = 'Отклонить платеж';
    protected $selector = '.text-red';

    /**
     * Назначить заявку на выплату платежа может только Администратор.
     *
     * @param Administrator $user
     * @param Collection $model
     * @return bool
     */
    public function authorize(Administrator $user, Collection $model)
    {
        return $user->isAdministrator();
    }

    public function dialog()
    {
        $this->confirm('Вы точно хотите отклонить спор?');
    }

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) {
            $model->status = Payment::STATUS_REJECTED;
            $model->save();
        }

        return $this->response()->success('Платеж отклонен')->refresh();
    }
}
