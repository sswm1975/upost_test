<?php

namespace App\Platform\Actions\Payment;

use App\Models\Payment;
use Encore\Admin\Actions\Response;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;

class AppointPayment extends RowAction
{
    public $name = 'Назначить менеджеру';
    protected $selector = '.text-green';

    public function authorize(Administrator $user, Payment $model): bool
    {
        return $user->isAdministrator() && $model->status == Payment::STATUS_NEW;
    }

    public function form()
    {
        $this->select('admin_user_id', 'Менеджер')
            ->options(Administrator::whereNotNull('user_id')->pluck('username', 'id'))
            ->rules('required');
    }

    public function handle(Payment $model, Request $request): Response
    {
        $model->admin_user_id = $request->get('admin_user_id');
        $model->status = Payment::STATUS_APPOINTED;
        $model->save();

        return $this->response()->success('Платеж назначен менеджеру')->refresh();
    }
}
