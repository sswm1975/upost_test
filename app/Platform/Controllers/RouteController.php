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
        $grid->column('user_id', 'Клиент')
            ->display(function($user_id) {
                return "<span class='nowrap'>{$this->user->full_name} ({$user_id})</span>";
            })
            ->sortable();
        $grid->column('from_country_id', 'Страна откуда')
            ->display(function(){
                return "<span class='nowrap'>{$this->from_country->name}</span>";
            })
            ->sortable();
        $grid->column('from_city_id', 'Город откуда')
            ->display(function ($value) {
                return !empty($value) ? "<span class='nowrap'>{$this->from_city->name}</span>" : '';
            })
            ->sortable();
        $grid->column('to_country_id', 'Страна куда')
            ->display(function(){
                return "<span class='nowrap'>{$this->to_country->name}</span>";
            })
            ->sortable();
        $grid->column('to_city_id', 'Город куда')
            ->display(function ($value) {
                return !empty($value) ? "<span class='nowrap'>{$this->to_city->name}</span>" : '';
            })
            ->sortable();
        $grid->column('deadline', 'Дата дедлайна')
            ->display(function($value){
                return "<span style='white-space: nowrap'>$value</span>";
            })
            ->sortable();
        $grid->column('looks', 'Просмотров')
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('status', 'Статус')
            ->display(function(){
                return "<span style='white-space: nowrap'>{$this->status_name}</span>";
            })
            ->sortable();
        $grid->column('created_at', 'Создано')
            ->display(function($value){
                return "<span style='white-space: nowrap'>$value</span>";
            })
            ->sortable();
        $grid->column('updated_at', 'Изменено')
            ->display(function($value){
                return "<span style='white-space: nowrap'>$value</span>";
            })
            ->sortable();

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
        return $this->addShowFields(new Show(Route::findOrFail($id)));
    }
}
