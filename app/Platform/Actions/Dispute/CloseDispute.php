<?php

namespace App\Platform\Actions\Dispute;

use App\Models\Dispute;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CloseDispute extends BatchAction
{
    public $name = 'Закрыть спор';

    protected $selector = '.close-disputes';

    public function handle(Collection $collection, Request $request)
    {
        if (! Admin::user()->inRoles(['administrator', 'dispute_manager'])) {
            return $this->response()->error('Операция запрещена');
        }

        foreach ($collection as $model) {
            $model->status = Dispute::STATUS_CLOSED;
            $model->save();
        }

        return $this->response()->success('Выполнено!')->refresh();
    }
}
