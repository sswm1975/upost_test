<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\WaitRange;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class WaitRangeController extends AdminController
{
    protected string $title = 'Диапазоны ожидания';
    protected string $icon = 'fa-calendar-plus-o';
    protected bool $enableStyleIndex = true;
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
        Admin::style($this->style());

        $grid = new Grid(new WaitRange);

        # SETTINGS GRID
        $grid->disableColumnSelector(false);
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
        });

        # COLUMNS
        $grid->column('id', 'Код')->sortable();
        $grid->column('name_uk', 'Название 🇺🇦');
        $grid->column('name_ru', 'Название 🇷🇺');
        $grid->column('name_en', 'Название 🇬🇧');
        $grid->column('days', 'Дней')->sortable();
        $grid->column('active', 'Действует')->switch(SWITCH_YES_NO)->sortable();
        $grid->column('order', 'Порядок')->orderable()->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new WaitRange);

        $form->display('id', 'Код');
        $form->text('name_uk', 'Название 🇺🇦')->required();
        $form->text('name_ru', 'Название 🇷🇺')->required();
        $form->text('name_en', 'Название 🇬🇧')->required();
        $form->currency('days', 'Дней')->symbol('∑')->digits(0)->rules('required|numeric');
        $form->number('order', 'Порядок ');
        $form->switch('active', 'Действует')->states(SWITCH_YES_NO);

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
        return $this->addShowFields(new Show(WaitRange::findOrFail($id)));
    }

    /**
     * Styles for index interface.
     *
     * @return string
     */
    protected function style(): string
    {
        return <<<EOT
            .column-selector > ul.dropdown-menu {width: 255px;}
            table > thead > tr > th {white-space: nowrap; background:lightgrey;}
            table > tbody > tr td.column-manager_id {padding-right: 20px;}
            table > tbody > tr td.column-manager_sip {padding-right: 25px;}
            .modal-header{cursor: move;}
            table th, .dataTable th {font-size: 11px;}
            .modal-backdrop {opacity:0 !important;}
            ul.products {margin: 0; padding: 0 0 0 10px;}
EOT;
    }
}
