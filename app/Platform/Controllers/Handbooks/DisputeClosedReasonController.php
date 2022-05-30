<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\DisputeClosedReason;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class DisputeClosedReasonController extends AdminController
{
    protected string $title = 'Причины закрытия спора';
    protected string $icon = 'fa-window-close-o';
    protected bool $isCreateButtonRight = true;
    protected array $breadcrumb = [
        ['text' => 'Справочники', 'icon' => 'book'],
    ];

    const GUILTY = [
        'performer' => 'Путешественник',
        'customer'  => 'Заказчик',
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new DisputeClosedReason);

        # SETTINGS GRID
        $grid->disableColumnSelector(false);

        $grid->quickSearch(function ($model, $search) {
            $model->quickSearch($search);
        })->placeholder('Поиск...');

        $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            $create->text('name', 'Название')->placeholder('Название')->required();
            $create->select('guilty', 'Виновен')->options(static::GUILTY)->required();
            $create->text('alias', 'Алиас')->placeholder('Алиас')->required();
        });

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align'=>'center'])->sortable();
        $grid->column('name', 'Название');
        $grid->column('guilty', 'Виновен')->replace(static::GUILTY)->sortable()->filter(static::GUILTY);
        $grid->column('alias', 'Алиас');
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
        $form = new Form(new DisputeClosedReason);

        $form->display('id', 'Код');
        $form->text('name', 'Название')->required();
        $form->select('guilty', 'Виновен')->options(static::GUILTY)->required();
        $form->text('alias', 'Алиас')->required()->help('Новый alias нужно добавить в конфигурационный файл system_messages');
        $form->display('created_at', 'Создано');
        $form->display('updated_at', 'Изменено');

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
        return $this->showFields(DisputeClosedReason::findOrFail($id));
    }
}
