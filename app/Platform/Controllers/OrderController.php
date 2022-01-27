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
        $grid->column('user_id', 'Код клиента')->sortable();
        $grid->column('user.full_name', 'ФИО клиента');
        $grid->column('name', 'Наименование')->sortable();
        $grid->column('slug', 'Слаг')->sortable();
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
        $grid->column('shop_slug', 'Магазин')->sortable();
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
        $grid->column('user_price_usd', 'Доход в $')
            ->filter('range')
            ->setAttributes(['align'=>'right'])
            ->sortable();

        $grid->column('not_more_price', 'Выше не принимать')
            ->showYesNo()
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('from_country.name', 'Страна откуда');
        $grid->column('from_city.name', 'Город откуда');
        $grid->column('to_country.name', 'Страна куда');
        $grid->column('to_city.name', 'Город куда');
        $grid->column('wait_range.name', 'Ждём');
        $grid->column('register_date', 'Зарегистрирован')->sortable();
        $grid->column('deadline', 'Дата дедлайна')->sortable();
        $grid->column('looks', 'Просмотров')->setAttributes(['align'=>'center'])->sortable();
        $grid->column('strikes', 'Жалобы')
            ->display(function($value) {
                if (empty($value)) return '';
                return count($value);
            })
            ->help('Количество жалоб')
            ->sortable();
        $grid->column('status', 'Статус')->showOtherField('status_name')->sortable();
        $grid->column('created_at', 'Создано')->sortable();
        $grid->column('updated_at', 'Изменено')->sortable();

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
        return $this->showFields(Order::findOrFail($id));
    }
}
