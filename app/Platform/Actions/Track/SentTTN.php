<?php

namespace App\Platform\Actions\Track;

use App\Models\Track;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Actions\Response;
use Illuminate\Support\Facades\Mail;

class SentTTN extends RowAction
{
    public $name = 'Отправить ТТН';
    protected $selector = '.text-green';

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

    public function handle(Track $model): Response
    {
        $ttn = $this->row()->ttn;
        $email = $this->row()->dispute->user->email;

        try {
//            Mail::raw("Номер ТТН {$ttn}", function($m) use ($email) {
//                $m->to($email)->subject('ТТН');
//            });
            $model->status = Track::STATUS_SENT;
            $model->save();
        } catch (\Exception $e) {
            return $this->response()->error($e->getMessage());
        }

        return $this->response()
            ->success('Успешно отправлено')
            ->refresh();
    }
}
