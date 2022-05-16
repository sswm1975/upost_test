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

    protected $selector = '.inwork-disputes';

    public function handle(Collection $collection, Request $request)
    {
        if (! Admin::user()->inRoles(['administrator', 'dispute_manager'])) {
            return $this->response()->error('Запрещено');
        }

        foreach ($collection as $model) {
            $model->status = Dispute::STATUS_IN_WORK;
            $model->save();
        }

        return $this->response()->success('Выполнено!')->refresh();
    }
}
