<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\Complaint;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class ComplaintController extends AdminController
{
    protected string $title = 'Жалобы';
    protected string $icon = 'fa-frown-o';
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
        $grid = new Grid(new Complaint);

        # SETTINGS GRID
        $grid->disableColumnSelector(false);

        # COLUMNS
        $grid->column('id', 'Код')->sortable();
        $grid->column('name_uk', 'Название 🇺🇦');
        $grid->column('name_ru', 'Название 🇷🇺');
        $grid->column('name_en', 'Название 🇬🇧');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new Complaint);

        $form->display('id', 'Код');
        $form->text('name_uk', 'Название 🇺🇦')->required();
        $form->text('name_ru', 'Название 🇷🇺')->required();
        $form->text('name_en', 'Название 🇬🇧')->required();

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
        return $this->addShowFields(new Show(Complaint::findOrFail($id)));
    }
}
