<?php

namespace App\Platform\Controllers;

use App\Models\Chat;
use App\Models\Dispute;
use App\Platform\Actions\Dispute\AppointDispute;
use App\Platform\Actions\Dispute\CancelDispute;
use App\Platform\Actions\Dispute\CloseDisputeGuiltyCustomer;
use App\Platform\Actions\Dispute\CloseDisputeGuiltyPerformer;
use App\Platform\Actions\Dispute\InWorkDispute;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class DisputeController extends AdminController
{
    protected string $title = 'Споры';
    protected string $icon = 'fa-gavel';

    /**
     * Формируем список меню в разрезе статусов споров.
     *
     * @return string
     */
    public function menu(): string
    {
        $counts = Dispute::selectRaw('status, count(1) as total')
            ->when(! Admin::user()->isAdministrator(), function ($query) {
                return $query->where('admin_user_id', Admin::user()->id);
            })
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = [];
        foreach (Dispute::STATUSES as $status) {
            if (Admin::user()->isRole('dispute_manager') && $status == Dispute::STATUS_ACTIVE) continue;

            $statuses[$status] = [
                'name' => __("message.dispute.statuses.$status"),
                'count' => $counts[$status] ?? 0,
            ];
        }

        return view('platform.disputes.menu')->with('statuses', $statuses);
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $status = request('status', Dispute::STATUS_ACTIVE);

        $grid = new Grid(new Dispute);

        $grid->disablePagination(false);
        $grid->disableColumnSelector(false);
        $grid->disableRowSelector(false);
        $grid->disableCreateButton();
        $grid->paginate(20);

        # Батчевые операции
        $grid->batchActions(function ($batch) use ($status) {
            # Назначить спор менеджеру
            if (Admin::user()->isAdministrator() && $status == Dispute::STATUS_ACTIVE) {
                $batch->add(new AppointDispute);
            }

            if (Admin::user()->inRoles(['administrator', 'dispute_manager'])) {
                # Взять спор в работу
                if ($status == Dispute::STATUS_APPOINTED) {
                    $batch->add(new InWorkDispute);
                }
                # Закрыть спор: 2 варианта
                if ($status == Dispute::STATUS_IN_WORK) {
                    $batch->add(new CloseDisputeGuiltyPerformer);
                    $batch->add(new CloseDisputeGuiltyCustomer);
                    $batch->add(new CancelDispute);
                }
            }
        });

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            if (! Admin::user()->isAdministrator()) {
                $actions->disableView();
                $actions->disableEdit();
            }
        });

        $grid->model()->addSelect(DB::raw('*, IFNULL((SELECT COUNT(1) FROM messages WHERE chat_id = disputes.chat_id), 0) AS messages_cnt'));

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
        $grid->column('message.text', 'Описание претензии')->limit(40);
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
            $grid->column('chat.lock_status', 'Статус блокировки')
                ->editable('select', Chat::LOCK_STATUSES)
                ->sortable();

            $grid->column('messages_cnt', 'Сообщений в чате')
                ->ajaxModal(ChatMessage::class, 700, 'chat_id')
                ->setAttributes(['align' => 'center'])
                ->sortable();

            $grid->column('unread_messages_count', 'Сообщений')
                ->display(function ($count) {
                    return $count ? "<span class='label label-danger'>$count</span>" : 'Новых нет';
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
        $statuses = [];
        foreach (Dispute::STATUSES as $status) {
            $statuses[$status] = __("message.dispute.statuses.$status");
        }

        $form = new Form(new Dispute);

        $form->display('id', 'Код');
        $form->date('deadline', 'Дата дедлайна');
        $form->select('chat.lock_status', 'Статус блокировки')->options(Chat::LOCK_STATUSES);

        if (Admin::user()->isAdministrator()) {
            $form->select('status', 'Статус')->options($statuses);
            $form->select('admin_user_id', 'Менеджер')->options(Administrator::pluck('username', 'id'));
        }

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
}
