<?php

namespace App\Platform\Actions\Payment;

use App\Models\Payment;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AppointPayment extends BatchAction
{
    public $name = 'Назначить менеджеру';
    protected $selector = '.text-green';

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

    public function form()
    {
        $this->select('admin_user_id', 'Менеджер')
            ->options(Administrator::whereNotNull('user_id')->pluck('username', 'id'))
            ->rules('required');
    }

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) {
            $model->admin_user_id = $request->get('admin_user_id');
            $model->status = Payment::STATUS_APPOINTED;
            $model->save();
        }

        return $this->response()->success('Платеж назначен менеджеру')->refresh();
    }
}
