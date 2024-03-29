<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RouteController extends BaseController
{
    protected string $title = 'Маршруты';
    protected string $icon = 'fa-location-arrow';
    protected string $entity = 'routes';
    protected int $count_columns = 21;

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $menu_statuses = [Route::STATUS_ACTIVE, Route::STATUS_CLOSED];

        $statuses = [];
        foreach ($menu_statuses as $status) {
            $statuses[$status] = __("message.route.statuses.$status");
        }
        $statuses['all'] =  'Все';

        return compact('statuses');
    }

    /**
     * Получить данные для таблицы.
     *
     * @return array
     */
    public function getData()
    {
        # узнаем кол-во споров в разрезе маршрутов
        $disputes = DB::table('rates')
            ->join('disputes', 'disputes.rate_id', '=', 'rates.id')
            ->select('route_id', DB::raw('count(1) as total'))
            ->groupBy('route_id')
            ->pluck('total', 'route_id')
            ->all();

        $status = request('status', Route::STATUS_ACTIVE);

        # отбираем маршруты
        $data = Route::with(['user', 'from_country', 'from_city', 'to_country', 'to_city', 'orders', 'orders.deductions'])
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get()
            ->map(function ($router) use ($disputes) {
                # подсчитываем сумму налогов и комиссий
                $order_tax_usd = $order_fee_usd = 0;
                foreach ($router->orders as $order) {
                    $order_tax_usd += $order->deductions->whereIn('type', ['tax_export', 'tax_import'])->sum('amount');
                    $order_fee_usd += $order->deductions->where('type', 'fee')->sum('amount');
                }

                return [
                    'id' => $router->id,
                    'status' => $router->status,
                    'user_id' => $router->user->id,
                    'user_full_name' => $router->user->full_name,
                    'from_country_id' => $router->from_country->id,
                    'from_country_name' => $router->from_country->name_en,
                    'from_city_id' => $router->from_city->id,
                    'from_city_name' => $router->from_city->name_en,
                    'to_country_id' => $router->to_country->id,
                    'to_country_name' => $router->to_country->name_en,
                    'to_city_id' => $router->to_city->id,
                    'to_city_name' => $router->to_city->name_en,
                    'deadline' => Carbon::createFromFormat('Y-m-d', $router->deadline)->format('d.m.Y'),
                    'created_at' => $router->created_at->format('d.m.Y'),
                    'updated_at' => $router->updated_at->format('d.m.Y'),
                    'orders_cnt' => $router->orders->count(),
                    'orders_price_usd' => $router->orders->sum('total_amount_usd'),
                    'orders_profit_usd' => $router->orders->sum('user_price_usd'),
                    'orders_tax_usd' => $order_tax_usd,
                    'order_fee_usd' => $order_fee_usd,
                    'disputes_cnt' => $disputes[$router->id] ?? 0,
                ];
            })
            ->all();

        return compact('data');
    }
}
