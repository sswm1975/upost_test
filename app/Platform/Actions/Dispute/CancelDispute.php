<?php

namespace App\Platform\Actions\Dispute;

use App\Models\Chat;
use App\Models\Dispute;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CancelDispute extends BatchAction
{
    public $name = 'Отменить спор';
    protected $selector = '.text-red';

    /**
     * Отклонить спор может или Администратор или закрепленный Менеджер по спорам.
     *
     * @param Administrator $user
     * @param Collection $model
     * @return bool
     */
    public function authorize(Administrator $user, Collection $model)
    {
        return $user->isAdministrator() || $user->id == $model->admin_user_id;
    }

    public function dialog()
    {
        $this->confirm('Вы точно хотите отменить выбранные споры?');
    }

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) {
            $model->status = Dispute::STATUS_CANCELED;
            $model->save();

            # информируем в чат об отклонении спора
            Chat::addSystemMessage($model->chat_id, 'dispute_canceled');
        }

        return $this->response()->success('Выполнено!')->refresh();
    }
}
