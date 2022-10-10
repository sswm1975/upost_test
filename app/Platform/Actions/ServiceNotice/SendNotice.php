<?php

namespace App\Platform\Actions\ServiceNotice;

use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\ServiceNotice;
use App\Models\User;
use Carbon\Carbon;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Actions\Response;
use Encore\Admin\Facades\Admin;

class SendNotice extends RowAction
{
    /**
     * Name action name.
     *
     * @return array|null|string
     */
    public function name()
    {
        return 'Отправить уведомление';
    }

    /**
     * Proccess clone operation.
     *
     * @param ServiceNotice $model
     *
     * @return Response
     */
    public function handle(ServiceNotice $model)
    {
        if (!active_notice_type($notice_type = NoticeType::SERVICE_NOTICE)) {
            return $this
                ->response()
                ->toastr()
                ->warning('Сервисное уведомление отключено!');
        }

        $model->sent_at = Carbon::now();
        $model->admin_user_id = Admin::user()->id;
        $model->save();

        $user_ids = User::where('status','active')->where('role', 'user')->pluck('lang', 'id');
        foreach ($user_ids as $user_id => $lang) {
            $info = [
                'text'    => $model->{"text_$lang"},
                'sent_at' => $model->sent_at->toDateTimeString(),
            ];

            Notice::create([
                'notice_type' => $notice_type,
                'user_id'     => $user_id,
                'object_id'   => $model->id,
                'data'        => $info,
            ]);
        }

        return $this
            ->response()
            ->toastr()
            ->success('Уведомление отправлено')
            ->refresh();
    }

    /**
     * Show dialog box.
     *
     * @return void
     */
    public function dialog()
    {
        $this->question(
            'Вы уверены, что хотите отправить уведомление?',
            '',
            [
                'confirmButtonText'  => 'Да',
                'confirmButtonColor' => '#d33',
            ]
        );
    }
}
