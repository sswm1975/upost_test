<?php

namespace App\Platform\Controllers;

use App\Models\Setting;
use App\Platform\Extensions\Exporters\ExcelExpoter;
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
}
