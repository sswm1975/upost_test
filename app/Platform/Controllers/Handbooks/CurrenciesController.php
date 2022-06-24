<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;

class CurrenciesController extends AdminController
{
    protected string $title = 'Курсы валют';
    protected string $icon = 'fa-usd';

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content): Content
    {
        $content->title($this->title())
            ->description($this->description())
            ->breadcrumb(...$this->breadcrumb());

        return $content
            ->body($this->grid())
            ->row($this->gridCurrencyRate());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        Admin::style(self::styleIndex());

        $grid = new Grid(new Currency);

        $grid->disableFilter();
        $grid->disableExport();
        $grid->disablePagination();
        $grid->disableRowSelector();
        $grid->disableColumnSelector();
        $grid->disableActions();
        $grid->disableCreateButton();

        $grid->column('id', 'Знак')->setAttributes(['align'=>'center']);
        $grid->column('symbol', 'Символ')->setAttributes(['align'=>'center']);
        $grid->column('code', 'Код')->setAttributes(['align'=>'center']);
        $grid->column('rate', 'Курс')->setAttributes(['align'=>'right']);
        $grid->column('created_at', 'Создано');
        $grid->column('updated_at', 'Изменено');

        return $grid;
    }

    /**
     * Построитель грида "История курсов валют"
     *
     * @return Grid
     */
    protected function gridCurrencyRate(): Grid
    {
        $grid = new Grid(new CurrencyRate);

        $grid->disableFilter();
        $grid->disableExport();
        $grid->disablePagination();
        $grid->disableRowSelector();
        $grid->disableColumnSelector();
        $grid->disableActions();
        $grid->disableCreateButton();

        $grid->tools(function ($tools) {
            $tools->append('<h4 class="text-navy"><i class="fa fa-history"></i>&nbsp;&nbsp;История курсов валют</h4>');
        });

        $grid->model()->selectRaw('
            date,
            SUM(IF(currency_id="₴", rate, NULL)) AS rate_uah,
            SUM(IF(currency_id="€", rate, NULL)) AS rate_eur,
            SUM(IF(currency_id="₽", rate, NULL)) AS rate_rub,
            MAX(created_at) AS created_at
        ')->groupBy('date')->latest('date');

        $grid->column('date', 'Дата');
        $grid->column('rate_uah', 'UAH')->setAttributes(['align' => 'right']);
        $grid->column('rate_eur', 'EUR')->setAttributes(['align' => 'right']);
        $grid->column('rate_rub', 'RUB')->setAttributes(['align' => 'right']);
        $grid->column('created_at', 'Загружено');

        return $grid;
    }
}
