<?php

namespace App\Platform\Controllers;

use App\Models\Route;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Platform\Extensions\Exporters\ExcelExpoter;

class RouteController extends AdminController
{
    protected string $title = 'Маршруты';
    protected string $icon = 'fa-location-arrow';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Route);

        $grid->disablePagination(false);
        $grid->disableExport(false);
        $grid->disableColumnSelector(false);
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->paginate(20);

        # COLUMNS
        $grid->column('id', 'Код')->sortable();
        $grid->column('user_id', 'Код клиента')->sortable();
        $grid->column('user.full_name', 'Клиент');
        $grid->column('from_country.name', 'Страна откуда');
        $grid->column('from_city.name', 'Город откуда');
        $grid->column('to_country.name', 'Страна куда');
        $grid->column('to_city.name', 'Город куда');
        $grid->column('deadline', 'Дата дедлайна')->sortable();
        $grid->column('status', 'Статус')->showOtherField('status_name')->sortable();
        $grid->column('created_at', 'Создано')->sortable();
        $grid->column('updated_at', 'Изменено')->sortable();
        $grid->column('viewed_orders_at', 'Просмотрено')->sortable();

        # EXPORT TO EXCEL
        $grid->exporter(new ExcelExpoter);

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
