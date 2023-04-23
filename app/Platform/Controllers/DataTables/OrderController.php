<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Order;
use Encore\Admin\Facades\Admin;

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
                    'deadline' => $order->deadline,
                    'wait_range' => $order->wait_range->id,
                    'created_at' => $order->created_at->toDateString(),
                    'updated_at' => $order->updated_at->toDateString(),
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

        $script = <<<SCRIPT
            var table = $('#orders').DataTable({
                ajax: '{$ajax_url}',
                processing: true,
                scrollX: true,
                stateSave:true,
                search: {
                    return: true
                },
                columns: [
                    { data: 'id', className: 'dt-body-center' },
                    { data: 'status' },
                    { data: 'user_id', className: 'dt-body-center' },
                    { data: 'user_full_name' },
                    { data: 'from_country_id' },
                    { data: 'from_country_name' },
                    { data: 'from_city_id', className: 'dt-body-center' },
                    { data: 'from_city_name' },
                    { data: 'to_country_id', className: 'dt-body-center' },
                    { data: 'to_country_name' },
                    { data: 'to_city_id', className: 'dt-body-center' },
                    { data: 'to_city_name' },
                    { data: 'price', className: 'dt-body-right' },
                    { data: 'currency', className: 'dt-body-center' },
                    { data: 'price_usd', className: 'dt-body-right' },
                    { data: 'user_price_usd', className: 'dt-body-right' },
                    { data: 'products_count', className: 'dt-body-center' },
                    { data: 'deadline', className: 'dt-body-center', render: DataTable.render.date() },
                    { data: 'created_at', className: 'dt-body-center', render: DataTable.render.date() },
                    { data: 'updated_at', className: 'dt-body-center', render: DataTable.render.date() },
                    { data: 'strikes' },
                ],
                order: [[0, 'desc']],
                language: {
                    processing: "Подождите...",
                    search: "Поиск:",
                    lengthMenu: "Показать _MENU_ записей",
                    info: "Записи с _START_ до _END_ из _TOTAL_ записей",
                    infoEmpty: "Записи с 0 до 0 из 0 записей",
                    infoFiltered: "(отфильтровано из _MAX_ записей)",
                    infoPostFix: "",
                    loadingRecords: "Загрузка записей...",
                    zeroRecords: "Записи отсутствуют.",
                    emptyTable: "В таблице отсутствуют данные",
                    paginate: {
                        first: "Первая",
                        previous: "«",
                        next: "»",
                        last: "Последняя"
                    },
                    aria: {
                        sortAscending: ": активировать для сортировки столбца по возрастанию",
                        sortDescending: ": активировать для сортировки столбца по убыванию"
                    }
                }
            });

            // возле поля Поиск добавляем кнопку "Очистить поле поиска"
            $('<button type="button" class="btn-danger"><i class="fa fa-times"></i></button>').appendTo('div.dataTables_filter');

            // нажата кнопка "Очистить поле поиска"
            $('.dataTables_filter').on('click', 'button', function() {
                table.search('').draw();
            });

SCRIPT;
        Admin::script($script);
    }
}
