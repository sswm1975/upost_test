<?php

namespace App\Platform\Actions\Dispute;

use App\Models\Dispute;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class InWorkDispute extends BatchAction
{
    public $name = 'Взять в работу';
    protected $selector = '.text-blue';

    public function authorize(Administrator $user, Collection $model)
    {
        return Admin::user()->inRoles(['administrator', 'dispute_manager']);
    }

    public function dialog()
    {
        $this->confirm('Вы точно хотите взять в работу спор?');
    }

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) {
            $model->status = Dispute::STATUS_IN_WORK;
            $model->save();
        }

        return $this->response()->success('Выполнено!')->refresh();
    }
}
