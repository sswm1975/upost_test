<?php

namespace App\Platform\Controllers\Handbooks;

use App\Platform\Extensions\ExcelExpoter;
use App\Models\UsSalesTax;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class UsSalesTaxController extends AdminController
{
    protected string $title = 'Податок з продажу в США';
    protected string $icon = 'fa-strikethrough';
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
        $grid = new Grid(new UsSalesTax);

        # SETTINGS GRID
        $grid->disableColumnSelector(false);
        $grid->quickSearch(function ($model, $query) {
                $model->where(function($model) use ($query) {
                    $model->where('code', "{$query}")
                        ->orWhere('name_uk', 'like', "%{$query}%")
                        ->orWhere('name_ru', 'like', "%{$query}%")
                        ->orWhere('name_en', 'like', "%{$query}%");

                });
            })->placeholder('Пошук по назві');
        $grid->disableExport(false);
        $grid->disableColumnSelector(false);


        # COLUMNS
        $grid->column('code', 'Код')->sortable();
        $grid->column('name_uk', 'Название 🇺🇦')->sortable();
        $grid->column('name_ru', 'Название 🇷🇺')->sortable();
        $grid->column('name_en', 'Название 🇬🇧')->sortable();
        $grid->column('tax_rate', 'Ставка')
            ->display(function($value){
                return "{$value}%";
            })
            ->setAttributes(['align'=>'right'])
            ->sortable();
        $grid->column('created_at', 'Створено')->hide()->sortable()->showDateTime('created_at');;
        $grid->column('updated_at', 'Змінено')->hide()->sortable()->showDateTime('updated_at');;

        $grid->exporter(new ExcelExpoter());

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new UsSalesTax);

        $form->display('id', 'Код');
        $form->text('name_uk', 'Название 🇺🇦')->required();
        $form->text('name_ru', 'Название 🇷🇺')->required();
        $form->text('name_en', 'Название 🇬🇧')->required();
        $form->currency('tax_rate', 'Ставка')->symbol('%')->digits(2)->rules('required|numeric');

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
        return $this->showFields(UsSalesTax::findOrFail($id));
    }
}
