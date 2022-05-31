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
    protected $selector = '.text-green';

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
            $model->status = Dispute::STATUS_APPOINTED;
            $model->save();
        }

        return $this->response()->success('Споры назначены менеджерам!')->refresh();
    }
}
