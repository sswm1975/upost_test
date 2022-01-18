<?php

namespace App\Platform\Controllers;

use App\Models\Order;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Platform\Extensions\Exporters\ExcelExpoter;

class OrderController extends AdminController
{
    protected string $title = 'Заказы';
    protected string $icon = 'fa-shopping-bag';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Order);

        # SETTINGS GRID
        $grid->quickSearch(function ($model, $query) {
            $model->where(function($model) use ($query) {
                $model->where('name', 'like', "%{$query}%")->orWhere('slug', 'like', "%{$query}%");

            });
        })->placeholder('Поиск по названию');

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
        $grid->column('name', 'Наименование')
            ->display(function($value){
                return "<span class='nowrap'>{$value}</span>";
            })
            ->sortable();
        $grid->column('slug', 'Слаг')
            ->display(function($value){
                return "<span class='nowrap'>{$value}</span>";
            })
            ->sortable();
        $grid->column('description_modal', 'Описание')
            ->modal('Описание', function () {
                $images = '';
                foreach ($this->images as $image) {
                    $images .= "<img src='$image' class='img img-thumbnail'>";
                }

                return "
                    <div>
                        <div style='width: 20%; float: left; padding-right: 10px;'>
                            $images
                        </div>
                        <div style='width: 80%; float: left;'>
                            {$this->description}
                        </div>
                        <div style='clear:both; line-height: 0;'></div>
                    </div>
                ";
            });
        $grid->column('product_link', 'Ссылка')
            ->display(function ($url){
                if (empty($url)) return '';
                $host = parse_url($url, PHP_URL_HOST);
                return "<a href='{$url}' target='_blank'>{$host}</a>";
            })
            ->sortable();
        $grid->column('products_count', 'Кол-во')
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('price', 'Цена')
            ->filter('range')
            ->setAttributes(['align'=>'right'])
            ->sortable();
        $grid->column('currency', 'Валюта')
            ->filter(array_combine(config('app.currencies'), config('app.currencies')))
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('price_usd', 'Цена в $')
            ->filter('range')
            ->setAttributes(['align'=>'right'])
            ->sortable();
        $grid->column('user_price', 'Доход')
            ->filter('range')
            ->setAttributes(['align'=>'right'])
            ->sortable();
        $grid->column('user_currency', 'Валюта дохода')
            ->filter(array_combine(config('app.currencies'), config('app.currencies')))
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('not_more_price', 'Выше не принимать')
            ->showYesNo()
            ->setAttributes(['align'=>'center'])
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
        $grid->column('wait_range_id', 'Ждём')
            ->display(function ($value) {
                return "<span class='nowrap'>{$this->wait_range->name}</span>";
            })
            ->sortable();
        $grid->column('register_date', 'Зарегистрирован')
            ->display(function($value){
                return "<span style='white-space: nowrap'>$value</span>";
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
        $grid->column('strikes', 'Жалобы')
            ->display(function($value) {
                if (empty($value)) return '';
                return count($value);
            })
            ->help('Количество жалоб')
            ->sortable();
        $grid->column('status', 'Статус')
            ->display(function(){
                return "<span style='white-space: nowrap'>{$this->status_name}</span>";
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
        return $this->addShowFields(new Show(User::findOrFail($id)));
    }
}
