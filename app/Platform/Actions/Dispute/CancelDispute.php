<?php

namespace App\Platform\Actions\Dispute;

use App\Models\Chat;
use App\Models\Dispute;
use Encore\Admin\Actions\Response;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;

class CancelDispute extends RowAction
{
    public $name = 'Отменить спор';
    protected $selector = '.text-red';

    public function authorize(Administrator $user, Dispute $model): bool
    {
        return $user->isAdministrator() || $user->id == $model->admin_user_id;
    }

    public function dialog()
    {
        $this->confirm('Вы точно хотите отменить выбранные споры?');
    }

    public function handle(Dispute $model, Request $request): Response
    {
        $model->status = Dispute::STATUS_CANCELED;
        $model->save();

        # информируем в чат об отклонении спора
        Chat::addSystemMessage($model->chat_id, 'dispute_canceled');

        return $this->response()
            ->success('Спор отменен!')
            ->refresh();
    }
}
