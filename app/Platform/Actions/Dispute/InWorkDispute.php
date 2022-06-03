<?php

namespace App\Platform\Actions\Dispute;

use App\Models\Dispute;
use Encore\Admin\Actions\Response;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class InWorkDispute extends RowAction
{
    public $name = 'Взять в работу';
    protected $selector = '.text-blue';

    public function authorize(Administrator $user, Dispute $model): bool
    {
        return Admin::user()->inRoles(['administrator', 'dispute_manager'])
            && $model->status == Dispute::STATUS_APPOINTED;
    }

    public function dialog()
    {
        $this->confirm('Вы точно хотите взять в работу спор?');
    }

    public function handle(Dispute $model, Request $request): Response
    {
        $model->status = Dispute::STATUS_IN_WORK;
        $model->save();

        return $this->response()
            ->success('Спор взят в работу!')
            ->refresh();
    }
}
