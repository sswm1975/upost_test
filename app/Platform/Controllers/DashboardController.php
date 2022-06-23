<?php

namespace App\Platform\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content): Content
    {
        return $content
            ->title('Доска')
            ->description('&nbsp;')
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $box = new Box('Заказы за последние 30 дней', $this->chart());
                    $column->append($box);
                });
            })
            ->row(function ($row) {
                if (Admin::user()->can('orders')) $row->column(6, static::ordersInfoBox());
                if (Admin::user()->can('routes')) $row->column(6, static::routesInfoBox());
            })
            ->row(function ($row) {
                if (Admin::user()->can('clients')) $row->column(6, static::clientsInfoBox());
                if (Admin::user()->can('disputes')) $row->column(6, static::disputesInfoBox());
            });
    }

    protected function chart(): string
    {
        $url = route('charts.sample_chart');

        Admin::script(<<<SCRIPT
new Chartisan({
    el: '#chart',
    url: '$url',
    hooks: new ChartisanHooks()
        .datasets('line')
        .colors()
});
SCRIPT);

        return '<div id="chart" style="height: 300px;"></div>';
    }

    /**
     * Плашка с количеством клиентов за день и за весь период.
     *
     * @return InfoBox
     */
    private static function clientsInfoBox(): InfoBox
    {
        $cnt = DB::table('users')
            ->selectRaw('COUNT(1) AS total, IFNULL(SUM(IF(register_date>?,1,0)),0) AS today', [date('Y-m-d') . ' 00:00:00'])
            ->where('id', '>', 0)
            ->first();

        return new InfoBox('Клиенты', 'users', 'yellow', route('platform.clients.index'), "{$cnt->today} / {$cnt->total}");
    }

    /**
     * Плашка с количеством заказов за день и за весь период.
     *
     * @return InfoBox
     */
    private static function ordersInfoBox(): InfoBox
    {
        $cnt = DB::table('orders')
            ->selectRaw('COUNT(1) AS total, IFNULL(SUM(IF(register_date>?,1,0)),0) AS today', [date('Y-m-d') . ' 00:00:00'])
            ->first();

        return new InfoBox('Заказы', 'shopping-bag', 'green', route('platform.orders.index'), "{$cnt->today} / {$cnt->total}");
    }

    /**
     * Плашка с количеством маршрутов за день и за весь период.
     *
     * @return InfoBox
     */
    private static function routesInfoBox(): InfoBox
    {
        $cnt = DB::table('routes')
            ->selectRaw('COUNT(1) AS total, IFNULL(SUM(IF(created_at>?,1,0)),0) AS today', [date('Y-m-d') . ' 00:00:00'])
            ->first();

        return new InfoBox('Маршруты', 'location-arrow', 'blue', route('platform.routes.index'), "{$cnt->today} / {$cnt->total}");
    }

    /**
     * Плашка с количеством споров за день и за весь период.
     *
     * @return InfoBox
     */
    private static function disputesInfoBox(): InfoBox
    {
        $cnt = DB::table('disputes')
            ->selectRaw('COUNT(1) AS total, IFNULL(SUM(IF(created_at>?,1,0)),0) AS today', [date('Y-m-d') . ' 00:00:00'])
            ->first();

        return new InfoBox('Споры', 'gavel', 'red', route('platform.disputes.index'), "{$cnt->today} / {$cnt->total}");
    }
}
