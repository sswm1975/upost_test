<?php

namespace App\Platform\Controllers\Old;

use App\Models\Chat;
use App\Models\Dispute;
use App\Platform\Controllers\AdminController;
use App\Platform\Actions\Dispute\AppointDispute;
use App\Platform\Actions\Dispute\CancelDispute;
use App\Platform\Actions\Dispute\CloseDisputeGuiltyCustomer;
use App\Platform\Actions\Dispute\CloseDisputeGuiltyPerformer;
use App\Platform\Actions\Dispute\InWorkDispute;
use App\Platform\Controllers\ChatMessage;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisputeController extends AdminController
{
    protected string $title = 'Споры';
    protected string $icon = 'fa-gavel';
    protected bool $enableDropdownAction = true;

    /**
     * Формируем список меню в разрезе статусов споров.
     *
     * @return array
     */
    public function menu(): array
    {
        $counts = Dispute::selectRaw('status, count(1) as total')
            ->when(! Admin::user()->isAdministrator(), function ($query) {
                return $query->where('admin_user_id', Admin::user()->id);
            })
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = [];
        foreach (Dispute::STATUSES as $status => $name) {
            if (Admin::user()->isRole('dispute_manager') && $status == Dispute::STATUS_ACTIVE) continue;

            $statuses[$status] = (object) [
                'name'  => $name,
                'count' => $counts[$status] ?? 0,
                'color' => Dispute::STATUS_COLORS[$status] ?? '',
            ];
        }

        return compact('statuses');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $status = request('status', Admin::user()->isAdministrator() ? Dispute::STATUS_ACTIVE : Dispute::STATUS_APPOINTED);

        $grid = new Grid(new Dispute);

        $grid->disablePagination(false)
            ->paginate(20)
            ->disableCreateButton();

        # ROW ACTIONS
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();

            # Назначить спор менеджеру
            if (Admin::user()->isAdministrator() && $actions->row->status == Dispute::STATUS_ACTIVE) {
                $actions->add(new AppointDispute);
            }

            # Взять спор в работу
            if ($actions->row->status == Dispute::STATUS_APPOINTED) {
                $actions->add(new InWorkDispute);
            }

            # Закрыть спор: 2 варианта
            if ($actions->row->status == Dispute::STATUS_IN_WORK) {
                $actions->add(new CloseDisputeGuiltyPerformer);
                $actions->add(new CloseDisputeGuiltyCustomer);
                $actions->add(new CancelDispute);
            }

            # в последнем статусе запрещаем все
            if (in_array($actions->row->status, [Dispute::STATUS_CLOSED, Dispute::STATUS_CANCELED])) {
                $actions->disableEdit();
                $actions->disableView();
            }
        });

        $grid->model()
            ->selectRaw('
                disputes.*,
                IFNULL((SELECT COUNT(1) FROM messages WHERE chat_id = disputes.chat_id), 0) AS messages_cnt,
                last_mess.user_id AS last_message_user_id
            ')
            ->leftJoin(
                DB::raw(
                    '(
                        SELECT m1.chat_id, m1.user_id
                        FROM messages m1
                        LEFT JOIN messages m2 ON (m1.chat_id = m2.chat_id AND m1.id < m2.id)
                        WHERE m2.id IS NULL
                    ) AS last_mess'
                ), 'last_mess.chat_id', '=', 'disputes.chat_id'
            );

        # FILTERS
        if (Admin::user()->isRole('dispute_manager')) {
            $grid->model()->where('admin_user_id', Admin::user()->id);
        }
        $grid->model()->where('status', $status);

        # COLUMNS
        $grid->column('id', 'Код')->sortable();
        $grid->column('problem_id', 'Код П.')->sortable();
        $grid->column('problem.name', 'Проблема');
        $grid->column('user_id', 'Код К.')->sortable();
        $grid->column('user.full_name', 'Клиент');
        $grid->column('text_modal', 'Описание претензии')
            ->modal('Описание претензии', function () {
                $images = '';
                foreach ($this->images as $image) {
                    $images .= "<img src='$image' class='img img-thumbnail'>";
                }

                return "
                    <div>
                        <div style='width: 20%; float: left; padding-right: 10px;'>
                            $images
                        </div>
                        <div style='width: 80%; float: left;'>
                            {$this->text}
                        </div>
                        <div style='clear:both; line-height: 0;'></div>
                    </div>
                ";
            })
            ->setAttributes(['align'=>'center']);
        $grid->column('problem.days', 'Дней')->help('Кол-во дней на рассмотрение проблемы');
        $grid->column('deadline', 'Дата дедлайна')
            ->display(function () {
                return isset($this->deadline) ? $this->deadline->format('Y-m-d') : '';
            })
            ->sortable();
        $grid->column('status', 'Статус')->showOtherField('status_name')->sortable();

        if ($status != Dispute::STATUS_ACTIVE) {
            $grid->column('admin_user_id', 'Код М.')->sortable();
            $grid->column('admin_user.username', 'Менеджер');
        }

        if (in_array($status, [Dispute::STATUS_IN_WORK, Dispute::STATUS_CLOSED])) {
            $grid->column('chat.lock_status', 'Статус блокировки Чата')
                ->editable('select', Chat::LOCK_STATUSES)
                ->sortable();

            $grid->column('messages_cnt', 'Сообщений в чате')
                ->ajaxModal(ChatMessage::class, 700, 'chat_id')
                ->setAttributes(['align' => 'center'])
                ->sortable();

            $grid->column('unread_messages_count', 'Unread')
                ->display(function ($count) {
                    $empty = $this->last_message_user_id > 0 ? "<span class='label label-default'>Прочитано, без ответа</span>" : '';
                    return $count ? "<span class='label label-danger'>$count</span>" : $empty;
                })
                ->setAttributes(['align' => 'center'])
                ->help('Количество непрочитанных сообщений');
        }

        if ($status == Dispute::STATUS_CLOSED) {
            $grid->column('dispute_closed_reason_id', 'Код причины закрытия')->sortable();
            $grid->column('reason_closing_description', 'Описание закрытия')->sortable();
        }

        $grid->column('created_at', 'Создано')->sortable();
        $grid->column('updated_at', 'Изменено')->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new Dispute);

        $form->display('id', 'Код');
        $form->date('deadline', 'Дата дедлайна');
        $form->select('chat.lock_status', 'Статус блокировки')->options(Chat::LOCK_STATUSES);

        return $form;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id): Show
    {
        return $this->showFields(Dispute::findOrFail($id));
    }

    /**
     * Очистить счетчик непрочитанных сообщений менеджером спора.
     *
     * @param int $chat_id
     * @return void
     */
    public function clearUnreadMessagesCount(int $chat_id)
    {
        Dispute::where('chat_id', $chat_id)->update(['unread_messages_count' => 0]);
    }

    /**
     * Получить количество споров по фильтру.
     * (используется App\Platform\Extensions\Nav\DisputesCounter - в хеадере счетчик споров)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDisputesCounter(Request $request): JsonResponse
    {
        $counter = Dispute::query()
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->get('status'));
            })
            ->when($request->filled('admin_user_id', 0), function ($query) use ($request) {
                return $query->where('admin_user_id', $request->get('admin_user_id'));
            })
            ->count();

        return response()->json(['value' => $counter]);
    }
}
