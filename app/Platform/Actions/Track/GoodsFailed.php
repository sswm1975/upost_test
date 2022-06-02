<?php

namespace App\Platform\Actions\Track;

use App\Models\Track;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Actions\Response;
use Illuminate\Database\Eloquent\Model;

class GoodsFailed extends RowAction
{
    public $name = 'Товар испорчен';

    /**
     * Обработчик.
     *
     * @param Model $model
     * @return Response
     */
    public function handle(Model $model)
    {
        $model->status = Track::STATUS_FAILED;
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
            "Товар испорчен по ТТН {$ttn}?",
            '',
            [
                'confirmButtonText'  => 'Да',
                'confirmButtonColor' => '#d33',
            ]
        );
    }

}
