<?php

namespace App\Platform\Controllers;

use App\Models\Chat;
use App\Models\Dispute;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class DisputeController extends AdminController
{
    protected string $title = 'Споры';
    protected string $icon = 'fa-bullhorn';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Dispute);

        $grid->disablePagination(false);
        $grid->disableColumnSelector(false);
        $grid->disableCreateButton();
        $grid->paginate(20);

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
        });

        # COLUMNS
        $grid->column('id', 'Код')->sortable();
        $grid->column('problem_id', 'Код проблемы')->sortable();
        $grid->column('problem.name', 'Проблема');
        $grid->column('user_id', 'Код клиента')->sortable();
        $grid->column('user.full_name', 'Клиент');
        $grid->column('message.text', 'Описание');
        $grid->column('problem.days', 'Дней')->help('Кол-во дней на рассмотрение проблемы');
        $grid->column('deadline', 'Дата дедлайна')
            ->display(function () {
                return isset($this->deadline) ? $this->deadline->format('Y-m-d') : '';
            })
            ->sortable();
        $grid->column('status', 'Статус')->showOtherField('status_name')->sortable();
        $grid->column('chat.lock_status', 'Статус блокировки')
            ->editable('select', Chat::LOCK_STATUSES)
            ->sortable();
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
        $form->select('status', 'Статус')->options($statuses);
        $form->select('chat.lock_status', 'Статус блокировки')
            ->options(Chat::LOCK_STATUSES);

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
