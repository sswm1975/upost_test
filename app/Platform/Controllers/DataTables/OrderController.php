<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Order;
use Illuminate\Support\Carbon;

class OrderController extends BaseController
{
    protected string $title = 'Заказы';
    protected string $icon = 'fa-shopping-bag';
    protected string $entity = 'orders';
    protected int $count_columns = 22;

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $menu_statuses = [Order::STATUS_ACTIVE, Order::STATUS_IN_WORK, Order::STATUS_SUCCESSFUL, Order::STATUS_CLOSED];

        $statuses = [];
        foreach ($menu_statuses as $status) {
            $statuses[$status] = __("message.order.statuses.$status");
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
        $status = request('status', Order::STATUS_ACTIVE);

        $data = Order::with(['user', 'from_country', 'from_city', 'to_country', 'to_city', 'wait_range'])
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'user_id' => $order->user->id,
                    'user_full_name' => $order->user->full_name,
                    'from_country_id' => $order->from_country->id,
                    'from_country_name' => $order->from_country->name_en,
                    'from_city_id' => $order->from_city->id,
                    'from_city_name' => $order->from_city->name_en,
                    'to_country_id' => $order->to_country->id,
                    'to_country_name' => $order->to_country->name_en,
                    'to_city_id' => $order->to_city->id,
                    'to_city_name' => $order->to_city->name_en,
                    'price' => $order->price,
                    'currency' => $order->currency,
                    'price_usd' => $order->price_usd,
                    'user_price_usd' => $order->user_price_usd,
                    'products_count' => $order->products_count,
                    'deadline' => Carbon::createFromFormat('Y-m-d', $order->deadline)->format('d.m.Y'),
                    'created_at' => $order->created_at->format('d.m.Y'),
                    'updated_at' => $order->updated_at->format('d.m.Y'),
                    'name' => $order->name,
                    'strikes' => implode(',', $order->strikes),
                ];
            })
            ->all();

        return compact('data');
    }
}
