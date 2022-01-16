<?php

namespace App\Platform\Controllers;

use App\Models\Setting;
use App\Platform\Extensions\Exporters\ExcelExpoter;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SettingController extends AdminController
{
    protected string $title = 'Настройки';
    protected string $icon = 'fa-gear';
    protected bool $enableStyleIndex = true;
    protected bool $isCreateButtonRight = true;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    public function grid(): Grid
    {
        Admin::style($this->style());

        $grid = new Grid(new Setting);

        # SETTINGS GRID
        $grid->disableExport(false);
        $grid->disableColumnSelector(false);
        $grid->quickSearch(function ($model, $query) {
            $model->where(function($model) use ($query) {
                $model->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
        })->placeholder('Поиск на наименованию и описанию');

        # COLUMNS
        $grid->column('id', 'Код')->sortable();
        $grid->column('name', 'Наименование')->copyable()->filter('like')->sortable();
        $grid->column('value', 'Значение')->filter('like')->sortable();
        $grid->column('description' , 'Описание')->filter('like');
        $grid->column('created_at', 'Дата добавления')->sortable();
        $grid->column('updated_at', 'Дата изменения')->sortable();

        # EXPORT TO EXCEL
        $grid->exporter(new ExcelExpoter());

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form(): Form
    {
        $form = new Form(new Setting);

        $form->display('id', 'Код');
        $form->text('name', 'Наименование')->required();
        $form->text('value', 'Значение')->required();
        $form->textarea('description', 'Описание');
        $form->display('created_at', 'Дата добавления');
        $form->display('updated_at', 'Дата изменения');

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
        return $this->addShowFields(new Show(Setting::findOrFail($id)));
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
