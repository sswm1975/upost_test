<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\DisputeProblem;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class DisputeProblemController extends AdminController
{
    protected string $title = 'Проблемы спора';
    protected string $icon = 'fa-exclamation-triangle';
    protected bool $isCreateButtonRight = true;
    protected array $breadcrumb = [
        ['text' => 'Справочники', 'icon' => 'book'],
    ];

    public function menu(): array
    {
        $counts = DisputeProblem::selectRaw('active, count(1) as total')
            ->groupBy('active')
            ->pluck('total', 'active')
            ->toArray();

        $statuses = [VALUE_ACTIVE => 'Действующие', VALUE_NOT_ACTIVE => 'Не активные'];
        foreach ($statuses as $status => $name) {
            $statuses[$status] = (object) [
                'name'  => $name,
                'count' => $counts[$status] ?? 0,
                'color' => $status ? 'success' : 'danger',
            ];
        }

        return compact('statuses');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new DisputeProblem);

        # FILTERS
        $grid->model()->where('active', request('status', VALUE_ACTIVE));

        # QUICK CREATE
        $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            $create->text('name_uk')->placeholder('Название 🇺🇦')->required();
            $create->text('name_ru')->placeholder('Название 🇷🇺')->required();
            $create->text('name_en')->placeholder('Название 🇬🇧')->required();
            $create->integer('days')->placeholder('Дней')->inputmask(['alias' => 'integer'])->width('60px')->required();

        });

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('name_uk', 'Название 🇺🇦');
        $grid->column('name_ru', 'Название 🇷🇺');
        $grid->column('name_en', 'Название 🇬🇧');
        $grid->column('days', 'Дней')->sortable();
        $grid->column('active', 'Действует')->switch(SWITCH_YES_NO)->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new DisputeProblem);

        $form->display('id', 'Код');
        $form->text('name_uk', 'Название 🇺🇦')->required();
        $form->text('name_ru', 'Название 🇷🇺')->required();
        $form->text('name_en', 'Название 🇬🇧')->required();
        $form->currency('days', 'Дней')->symbol('∑')->digits(0)->rules('required|numeric');
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
        return $this->showFields(DisputeProblem::findOrFail($id));
    }
}
