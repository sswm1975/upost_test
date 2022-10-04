<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\NoticeType;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;

class NoticeTypeController extends AdminController
{
    protected string $title = 'Типы уведомлений';
    protected string $icon = 'fa-bell';
    protected bool $isCreateButtonRight = true;
    protected array $breadcrumb = [
        ['text' => 'Справочники', 'icon' => 'book'],
    ];

    public function menu(): array
    {
        $counts = NoticeType::selectRaw('active, count(1) as total')
            ->groupBy('active')
            ->pluck('total', 'active')
            ->toArray();

        foreach (VALUES_ACTING as $status => $name) {
            $statuses[$status] = (object) [
                'name'  => $name,
                'count' => $counts[$status] ?? 0,
                'color' => $status ? 'success' : 'danger',
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
        Admin::style('.modal-body > p {word-wrap: break-word;white-space: normal;text-align:left;}');

        $grid = new Grid(new NoticeType);

        $grid->model()->where('active', request('status', VALUE_ACTIVE));

        $grid->quickSearch(['id', 'title'])->placeholder('Поиск...');

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
        });

        $grid->column('id', 'Код')->sortable();
        $grid->column('title', 'Наименование')
            ->modal('Описание', function($model) {
                return !empty($model->description) ? $model->description : 'Нет описания';
            })
            ->sortable();
        $grid->column('name_uk', 'Уведомление 🇺🇦');
        $grid->column('name_ru', 'Уведомление 🇷🇺');
        $grid->column('name_en', 'Уведомление 🇬🇧');
        $grid->column('active', 'Действует')->switch(SWITCH_YES_NO)->sortable();
        $grid->column('created_at', 'Создано');
        $grid->column('updated_at', 'Изменено');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new NoticeType);

        $form->text('id', 'Код')
            ->creationRules(['required', "unique:notice_types"])
            ->updateRules(['required', "unique:notice_types,id,{{id}}"]);
        $form->text('title', 'Наименование')->required();
        $form->text('name_uk', 'Уведомление 🇺🇦')->required();
        $form->text('name_ru', 'Уведомление 🇷🇺')->required();
        $form->text('name_en', 'Уведомление 🇬🇧')->required();
        $form->ckeditor('description', 'Описание');
        $form->switch('active', 'Действует')->default(1)->states(SWITCH_YES_NO);

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
        return $this->showFields(NoticeType::findOrFail($id));
    }
}
