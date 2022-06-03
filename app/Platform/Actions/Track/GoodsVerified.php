<?php

namespace App\Platform\Actions\Track;

use App\Models\Track;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Actions\Response;

class GoodsVerified extends RowAction
{
    public $name = 'Товар проверен';
    protected $selector = '.text-green';

    public function dialog()
    {
        $ttn = $this->row()->ttn;

        $this->question(
            "Товар проверен по ТТН {$ttn}?",
            '',
            [
                'confirmButtonText'  => 'Да',
                'confirmButtonColor' => '#d33',
            ]
        );
    }

    public function handle(Track $model): Response
    {
        $model->status = Track::STATUS_VERIFIED;
        $model->save();

        return $this->response()
            ->success('Успешно изменён статус трека')
            ->refresh();
    }
}
