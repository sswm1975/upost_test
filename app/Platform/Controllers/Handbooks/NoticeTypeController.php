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

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        Admin::style('.modal-body > p {word-wrap: break-word;white-space: normal;text-align:left;}');

        $grid = new Grid(new NoticeType);

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
        });

        $grid->column('id', 'Код')->sortable();
        $grid->column('name_uk', 'Уведомление 🇺🇦');
        $grid->column('name_ru', 'Уведомление 🇷🇺');
        $grid->column('name_en', 'Уведомление 🇬🇧');
        $grid->column('active', 'Действует')->switch(SWITCH_YES_NO)->sortable();
        $grid->column('description_modal', 'Описание')
            ->setAttributes(['align'=>'center'])
            ->modal('Описание', function($model) {
                return !empty($model->description) ? $model->description : 'Нет описания';
            });
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
        $form->text('name_uk', 'Название 🇺🇦')->required();
        $form->text('name_ru', 'Название 🇷🇺')->required();
        $form->text('name_en', 'Название 🇬🇧')->required();
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
