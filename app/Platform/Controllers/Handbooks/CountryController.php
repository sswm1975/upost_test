<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\Country;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CountryController  extends AdminController
{
    protected string $title = 'Страны';
    protected string $icon = 'fa-building';
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
        $grid = new Grid(new Country);

        # SETTINGS GRID
        $grid->disableColumnSelector(false);
        $grid->disablePagination(false);
        $grid->paginate(20);
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
        });

        $grid->quickSearch(function ($model, $query) {
            $model->where(function($model) use ($query) {
                $model->where('name_uk', 'like', "%{$query}%")
                    ->orWhere('name_ru', 'like', "%{$query}%")
                    ->orWhere('name_en', 'like', "%{$query}%");

            });
        })->placeholder('Поиск по названию');

        # COLUMNS
        $grid->column('id')->sortable();
        $grid->column('alpha3')->sortable();
        $grid->column('code')->sortable();
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
        $form = new Form(new Country);

        $form->text('id', 'ISO 3166-1 alpha-2 code')->required();
        $form->text('alpha3', 'ISO 3166-1 alpha-3 code')->required();
        $form->text('code', 'ISO 3166-1 num-3 code')->required();
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
        return $this->showFields(Country::findOrFail($id));
    }
}
