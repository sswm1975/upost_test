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
        CUSTOMER  => 'Заказчик',
        PERFORMER => 'Исполнитель',
    ];

    public function menu(): array
    {
        $counts = DisputeClosedReason::selectRaw('guilty, count(1) as total')
            ->groupBy('guilty')
            ->pluck('total', 'guilty')
            ->toArray();

        $statuses = [];
        foreach (self::GUILTY as $status => $name) {
            $statuses[$status] = (object) [
                'name'  => $name,
                'count' => $counts[$status] ?? 0,
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
        $grid = new Grid(new DisputeClosedReason);

        # FILTERS
        $grid->model()->where('guilty', request('status', array_key_first(self::GUILTY)));

        # QUICK CREATE
        $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            $create->text('name', 'Название')->placeholder('Название')->required();
            $create->select('guilty', 'Виновен')->options(self::GUILTY)->required();
            $create->text('alias', 'Алиас')->placeholder('Алиас')->required();
        });

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align'=>'center'])->sortable();
        $grid->column('name', 'Название');
        $grid->column('guilty', 'Виновен')->replace(self::GUILTY)->sortable()->filter(self::GUILTY);
        $grid->column('alias', 'Алиас');

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
        $form->select('guilty', 'Виновен')->options(self::GUILTY)->required();
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
