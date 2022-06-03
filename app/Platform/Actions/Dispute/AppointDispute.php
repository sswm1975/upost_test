<?php

namespace App\Platform\Actions\Dispute;

use App\Models\Dispute;
use Encore\Admin\Actions\Response;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;

class AppointDispute extends RowAction
{
    public $name = 'Назначить спор менеджеру';
    protected $selector = '.text-green';

    public function authorize(Administrator $user, Dispute $model): bool
    {
        return $user->isAdministrator()
            && $model->status == Dispute::STATUS_ACTIVE;
    }

    public function form()
    {
        $this->select('admin_user_id', 'Менеджер')
            ->options(Administrator::whereNotNull('user_id')->pluck('username', 'id'))
            ->rules('required');
    }

    public function handle(Dispute $model, Request $request): Response
    {
        $model->admin_user_id = $request->get('admin_user_id');
        $model->status = Dispute::STATUS_APPOINTED;
        $model->save();

        return $this->response()
            ->success('Спор назначен менеджеру!')
            ->refresh();
    }
}
