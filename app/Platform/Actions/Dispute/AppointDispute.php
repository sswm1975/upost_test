<?php

namespace App\Platform\Actions\Dispute;

use App\Models\Dispute;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AppointDispute extends BatchAction
{
    public $name = 'Назначить спор менеджеру';

    protected $selector = '.appoint-disputes';

    public function handle(Collection $collection, Request $request)
    {
        if (! Admin::user()->isAdministrator()) {
            return $this->response()->error('Операция запрещена');
        }

        foreach ($collection as $model) {
            $model->admin_user_id = $request->get('admin_user_id');
            $model->status = Dispute::STATUS_APPOINTED;
            $model->save();
        }

        return $this->response()->success('Споры назначены менеджерам!')->refresh();
    }

    public function form()
    {
        $this->select('admin_user_id', 'Менеджер')
            ->options(Administrator::whereNotNull('user_id')->pluck('username', 'id'))
            ->rules('required');
    }
}
