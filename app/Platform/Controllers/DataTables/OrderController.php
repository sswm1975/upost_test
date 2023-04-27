<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Order;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Carbon;

class OrderController extends BaseController
{
    protected string $title = 'Заказы';
    protected string $icon = 'fa-shopping-bag';
    protected string $view = 'platform.datatables.orders.table';

    public function getOrders()
    {
        $orders = Order::with(['user', 'from_country', 'from_city', 'to_country', 'to_city', 'wait_range'])
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
                    'wait_range' => $order->wait_range->id,
                    'created_at' => $order->created_at->format('d.m.Y'),
                    'updated_at' => $order->updated_at->format('d.m.Y'),
                    'name' => $order->name,
                    'description' => $order->description,
                    'strikes' => implode(',', $order->strikes),
                ];
            })
            ->all();

        return ['data' => $orders];
    }

    protected function scriptDataTable()
    {
        $ajax_url = route('platform.ajax.orders');

        $script = getScript('platform.datatables.orders.script', compact('ajax_url'));

        Admin::script($script);
    }
}
