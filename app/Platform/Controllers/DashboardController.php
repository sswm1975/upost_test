<?php

namespace App\Platform\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
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
            ->row(function ($row) {
                $row->column(6, static::ordersInfoBox());
                $row->column(6, static::routesInfoBox());
            })
            ->row(function ($row) {
                $row->column(6, static::clientsInfoBox());
                $row->column(6, static::disputesInfoBox());
            });

    }

    /**
     * Плашка с количеством клиентов за день и за весь период.
     *
     * @return InfoBox
     */
    private static function clientsInfoBox(): InfoBox
    {
        $cnt = DB::table('users')
            ->selectRaw('COUNT(1) AS total, SUM(IF(register_date>?,1,0)) AS today', [date('Y-m-d') . ' 00:00:00'])
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
            ->selectRaw('COUNT(1) AS total, SUM(IF(register_date>?,1,0)) AS today', [date('Y-m-d') . ' 00:00:00'])
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
            ->selectRaw('COUNT(1) AS total, SUM(IF(created_at>?,1,0)) AS today', [date('Y-m-d') . ' 00:00:00'])
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
            ->selectRaw('COUNT(1) AS total, SUM(IF(created_at>?,1,0)) AS today', [date('Y-m-d') . ' 00:00:00'])
            ->first();

        return new InfoBox('Споры', 'bullhorn', 'red', route('platform.disputes.index'), "{$cnt->today} / {$cnt->total}");
    }
}
