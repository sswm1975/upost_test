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
                    $column->append(new Box('', $this->chart()));
                });
            })
            ->row(function ($row) {
                if (Admin::user()->can('orders')) $row->column(3, static::ordersInfoBox());
                if (Admin::user()->can('routes')) $row->column(3, static::routesInfoBox());
                if (Admin::user()->can('clients')) $row->column(3, static::clientsInfoBox());
                if (Admin::user()->can('disputes')) $row->column(3, static::disputesInfoBox());
            });
    }

    protected function chart(): string
    {
        $rows = DB::select('
            SELECT
              DATE_FORMAT(g.arcdate, "%d.%m") AS arcdate,
                COUNT(o.id) AS orders_cnt,
                COUNT(r.id) AS routes_cnt,
                COUNT(u.id) AS clients_cnt,
                COUNT(d.id) AS disputes_cnt
            FROM (
              SELECT CURDATE() - INTERVAL (g1.idx + (10 * g2.idx)) DAY AS arcdate
              FROM (SELECT 0 AS idx UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS g1
              CROSS JOIN (SELECT 0 AS idx UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS g2
            ) g
            LEFT JOIN orders o ON o.register_date = g.arcdate
            LEFT JOIN routes r ON DATE(r.created_at) = g.arcdate
            LEFT JOIN users u ON u.register_date = g.arcdate
            LEFT JOIN disputes d ON DATE(d.created_at) = g.arcdate
            WHERE g.arcdate >= CURDATE() - INTERVAL 30 DAY
            GROUP BY g.arcdate
        ');

        $dates = json_encode(array_column($rows,'arcdate'));
        $orders_cnt = json_encode(array_column($rows,'orders_cnt'));
        $routes_cnt = json_encode(array_column($rows,'routes_cnt'));
        $clients_cnt = json_encode(array_column($rows,'clients_cnt'));
        $disputes_cnt = json_encode(array_column($rows,'disputes_cnt'));

        Admin::script(<<<SCRIPT
            const colors = ['#00A65A', '#0073B7', '#f39c12', '#dd4b39'];
            let myChart = echarts.init(document.getElementById('chart_orders'));
            myChart.setOption({
              color: colors,
              title: {text: 'Количественные показатели за последние 30 дней'},
              legend: {},
              grid: {left: 0, right: '30px', top:'100px', bottom: 0, containLabel: true},
              tooltip: {trigger: 'axis'},
              xAxis: {type: 'category', data: $dates},
              yAxis: {type: 'value'},
              series: [
                  {
                    name: 'Заказы',
                    data: $orders_cnt, type: 'line', smooth: true,
                    markPoint: {data: [{type: 'max', name: 'Max'}]},
                    markLine: {data: [{type: 'average', name: 'Avg'}]}
                  },
                  {
                    name: 'Маршруты',
                    data: $routes_cnt, type: 'line', smooth: true,
                    markPoint: {data: [{type: 'max', name: 'Max'}]},
                    markLine: {data: [{type: 'average', name: 'Avg'}]}
                  },
                  {
                    name: 'Клиенты',
                    data: $clients_cnt, type: 'line', smooth: true,
                    markPoint: {data: [{type: 'max', name: 'Max'}]},
                    markLine: {data: [{type: 'average', name: 'Avg'}]}
                  },
                  {
                    name: 'Споры',
                    data: $disputes_cnt, type: 'line', smooth: true,
                    markPoint: {data: [{type: 'max', name: 'Max'}]},
                    markLine: {data: [{type: 'average', name: 'Avg'}]}
                  },
              ]
            });
SCRIPT);

        return '<div id="chart_orders" style="width:100%;height:400px;"></div>';
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
