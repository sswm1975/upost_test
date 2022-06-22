<?php

namespace App\Platform\Controllers;

use App\Models\Order;
use App\Models\Shop;
use App\Platform\Exporters\OrderExcelExporter;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class OrderController extends AdminController
{
    protected string $title = 'Заказы';
    protected string $icon = 'fa-shopping-bag';

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $counts = Order::selectRaw('status, count(1) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = [];
        foreach (Order::STATUSES as $status) {
            $statuses[$status] = (object) [
                'name'  => __("message.order.statuses.$status"),
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
        $grid = new Grid(new Order);

        # SETTINGS GRID
        $grid->disablePagination(false);
        $grid->disableFilter(false);
        $grid->disableExport(false);
        $grid->disableRowSelector(false);
        $grid->disableColumnSelector(false);
        $grid->disableCreateButton();
        $grid->paginate(20);

        $grid->quickSearch(function ($model, $query) {
            $model->where(function($model) use ($query) {
                $model->where('name', 'like', "%{$query}%")->orWhere('slug', 'like', "%{$query}%");

            });
        })->placeholder('Поиск по названию');

        # EXPORT TO EXCEL
        $grid->exporter(new OrderExcelExporter);

        # MODEL FILTERS & SORT
        $grid->model()
            ->where('status', request('status', Order::STATUS_ACTIVE))
            ->with('deductions')
            ->withCount('deductions');
        if (! request()->has('_sort')) {
            $grid->model()->latest('id');
        }

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('user_id', 'Код клиента')->setAttributes(['align' => 'center'])->sortable();
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
            })
            ->setAttributes(['align'=>'center']);
        $grid->column('product_link', 'Ссылка')
            ->display(function ($url){
                if (empty($url)) return '';
                $host = parse_url($url, PHP_URL_HOST);
                return "<a href='{$url}' target='_blank'>{$host}</a>";
            })
            ->sortable();
        $grid->column('shop_slug', 'Магазин')
            ->filter(Shop::pluck('name', 'slug')->toArray())
            ->sortable();
        $grid->column('products_count', 'Кол-во')
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('price', 'Цена')
            ->price()
            ->filter('range')
            ->setAttributes(['align'=>'right'])
            ->sortable();
        $grid->column('total_amount', 'Сумма')
            ->price()
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('currency', 'Валюта')
            ->filter(array_combine(config('app.currencies'), config('app.currencies')))
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('price_usd', 'Цена в $')
            ->price()
            ->filter('range')
            ->setAttributes(['align'=>'right'])
            ->sortable();
        $grid->column('taxes_fees_modal', 'Taxes/Fees')
            ->modal('Удержания: Налоги и комиссии', function ($model) {
                $deductions = $model->deductions->map(function ($deductions) {
                    return $deductions->only(['id', 'type', 'name', 'amount', 'created_at', 'updated_at']);
                })->toArray();

                return new Table(['Код', 'Тип удержания', 'Наименование', 'Сумма', 'Добавлено', 'Изменено'], $deductions);
            })
            ->display(function($value) {
                if (empty($this->deductions_count)) return '';

                return "{$value}&nbsp;<span class='label label-default'>{$this->deductions_count}</span>";
            })
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('user_price', 'Доход')
            ->price()
            ->filter('range')
            ->setAttributes(['align'=>'right'])
            ->sortable();
        $grid->column('user_currency', 'Валюта дохода')
            ->filter(array_combine(config('app.currencies'), config('app.currencies')))
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('user_price_usd', 'Доход в $')
            ->price()
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
        $grid->column('wait_range.name', 'Готов ждать');
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

        # ROW ACTIONS
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

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
