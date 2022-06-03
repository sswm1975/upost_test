<?php

namespace App\Platform\Actions\Track;

use App\Models\Track;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Actions\Response;

class GoodsFailed extends RowAction
{
    public $name = 'Товар испорчен';
    protected $selector = '.text-red';

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

    public function handle(Track $model): Response
    {
        $model->status = Track::STATUS_FAILED;
        $model->save();

        return $this->response()
            ->success('Успешно изменён статус трека')
            ->refresh();
    }
}
