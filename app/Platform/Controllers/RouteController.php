<?php

namespace App\Platform\Controllers;

use App\Models\Route;
use App\Models\User;
use App\Platform\Exporters\RouteExcelExporter;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RouteController extends AdminController
{
    protected string $title = 'Маршруты';
    protected string $icon = 'fa-location-arrow';

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $counts = Route::selectRaw('status, count(1) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = [];
        foreach (array_diff(Route::STATUSES, [Route::STATUS_ALL]) as $status) {
            $statuses[$status] = (object) [
                'name'  => __("message.route.statuses.$status"),
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
        $grid = new Grid(new Route);

        # SETTINGS GRID
        $grid->disablePagination(false);
        $grid->disableFilter(false);
        $grid->disableExport(false);
        $grid->disableRowSelector(false);
        $grid->disableColumnSelector(false);
        $grid->disableCreateButton();
        $grid->paginate(20);

        # FILTERS
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $users = User::where('role', User::ROLE_USER)
                    ->get(['id', 'name', 'surname'])
                    ->pluck('full_name', 'id')
                    ->toArray();
                $filter->equal('user_id', 'Клиент')->select($users);
            });
        });

        # MODEL FILTERS & SORT
        $grid->model()->where('status', request('status', Route::STATUS_ACTIVE));
        if (! request()->has('_sort')) {
            $grid->model()->latest('id');
        }

        # ROW ACTIONS
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('user_id', 'Код К.')->setAttributes(['align' => 'center'])->filter()->sortable();
        $grid->column('user.full_name', 'Клиент');
        $grid->column('from_country.name', 'Страна откуда');
        $grid->column('from_city.name', 'Город откуда');
        $grid->column('to_country.name', 'Страна куда');
        $grid->column('to_city.name', 'Город куда');
        $grid->column('deadline', 'Дата дедлайна');
        $grid->column('status', 'Статус')->showOtherField('status_name');
        $grid->column('created_at', 'Создано');
        $grid->column('updated_at', 'Изменено');
        $grid->column('viewed_orders_at', 'Просмотрено');

        # EXPORT TO EXCEL
        $grid->exporter(new RouteExcelExporter);

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id): Show
    {
        return $this->showFields(Route::findOrFail($id));
    }
}
