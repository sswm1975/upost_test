<?php

namespace App\Platform\Actions\Track;

use App\Models\Track;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Actions\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class SentTTN extends RowAction
{
    public $name = 'Отправить ТТН';

    /**
     * Обработчик.
     *
     * @param Model $model
     * @return Response
     */
    public function handle(Model $model)
    {
        $ttn = $this->row()->ttn;
        $email = $this->row()->dispute->user->email;

//        Mail::raw("Номер ТТН {$ttn}", function($m) use ($email) {
//            $m->to($email)->subject('ТТН');
//        });

        $model->status = Track::STATUS_SENT;
        $model->save();

        return $this->response()->success('Успешно отправлено')->refresh();
    }

    /**
     * Show dialog box.
     *
     * @return void
     */
    public function dialog()
    {
        $ttn = $this->row()->ttn;
        $email = $this->row()->dispute->user->email;

        $this->question(
            "Отправить ТТН {$ttn} на емейл {$email}?",
            '',
            [
                'confirmButtonText'  => 'Да',
                'confirmButtonColor' => '#d33',
            ]
        );
    }

}
