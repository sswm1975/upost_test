<?php

namespace App\Platform\Actions\Track;

use App\Models\Track;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Actions\Response;
use Illuminate\Database\Eloquent\Model;

class GoodsReceived extends RowAction
{
    public $name = 'Товар получен';

    /**
     * Обработчик.
     *
     * @param Model $model
     * @return Response
     */
    public function handle(Model $model)
    {
        $model->status = Track::STATUS_RECEIVED;
        $model->save();

        return $this->response()->success('Успешно изменён статус трека')->refresh();
    }

    /**
     * Show dialog box.
     *
     * @return void
     */
    public function dialog()
    {
        $ttn = $this->row()->ttn;

        $this->question(
            "Товар получен по ТТН {$ttn}?",
            '',
            [
                'confirmButtonText'  => 'Да',
                'confirmButtonColor' => '#d33',
            ]
        );
    }

}
