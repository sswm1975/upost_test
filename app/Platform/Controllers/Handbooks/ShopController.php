<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\Shop;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class ShopController extends AdminController
{
    protected string $title = 'Магазины';
    protected string $icon = 'fa-building-o';
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
        $grid = new Grid(new Shop);

        $grid->disableColumnSelector(false);

        $grid->quickSearch('name')->placeholder('Поиск по наименованию');

        $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            $create->text('name', 'Наименование')->placeholder('Наименование')->required();
            $create->text('slug', 'Слаг')->placeholder('Слаг')->required();
            $create->url('url', 'Ссылка')->placeholder('Ссылка');
        });

        $grid->column('id', 'Код')->sortable();
        $grid->column('name', 'Наименование')->sortable();
        $grid->column('slug', 'Слаг')->sortable();
        $grid->column('url', 'Ссылка')->link();
        $grid->column('active', 'Действует')->switch(SWITCH_YES_NO)->sortable();
        $grid->column('created_at', trans('admin.created_at'))->sortable();
        $grid->column('updated_at', trans('admin.updated_at'))->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form(): Form
    {
        $form = new Form(new Shop);

        $form->display('id', 'Код');
        $form->text('name', 'Наименование')->required();
        $form->text('slug', 'Слаг')->required();
        $form->url('url', 'Ссылка');
        $form->switch('active', 'Действует')->default(1)->states(SWITCH_YES_NO);
        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

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
        return $this->showFields(Shop::findOrFail($id));
    }
}
