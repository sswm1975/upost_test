<?php

namespace App\Platform\Controllers\Settings;

use App\Models\Constant;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ConstantController extends AdminController
{
    protected string $title = 'Константы';
    protected string $icon = 'fa-gear';
    protected bool $isCreateButtonRight = true;
    protected bool $enableDropdownAction = true;
    protected array $breadcrumb = [
        ['text' => 'Настройки', 'icon' => 'cogs'],
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    public function grid(): Grid
    {
        $grid = new Grid(new Constant);

        # SETTINGS GRID
        $grid->quickSearch(function ($model, $query) {
            $model->where(function($model) use ($query) {
                $model->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
        })->placeholder('Поиск на наименованию и описанию');

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('name', 'Наименование')->copyable()->filter('like')->sortable();
        $grid->column('value', 'Значение')->filter('like')->sortable();
        $grid->column('description' , 'Описание')->filter('like');
        $grid->column('created_at', 'Дата добавления')->sortable();
        $grid->column('updated_at', 'Дата изменения')->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form(): Form
    {
        $form = new Form(new Constant);

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
        return $this->showFields(Constant::findOrFail($id));
    }
}
