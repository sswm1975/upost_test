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

        $script = <<<SCRIPT
            $.fn.dataTable.moment( 'DD.MM.YYYY' );
            $.fn.dataTable.moment( 'DD.MM.YYYY' );
            $.fn.dataTable.moment( 'DD.MM.YYYY' );

            // локализация для плагина DateTime
            $.extend($.fn.dataTable.DateTime.defaults.i18n, {
                previous: "Предыдущий",
                next: "Следующий",
                hours: "Часы",
                minutes: "Минуты",
                seconds: "Секунды",
                unknown: "Неизвестный",
                amPm: ["AM", "PM"],
                months: {"0": "Январь", "1": "Февраль", "2": "Март", "3": "Апрель", "4": "Май", "5": "Июнь", "6": "Июль", "7": "Август", "8": "Сентябрь", "9": "Октябрь", "10": "Ноябрь", "11": "Декабрь"},
                weekdays: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"]
            });

            var table = $('#orders').DataTable({
                dom: 'Bfrtip',
                lengthMenu: [
                    [ 10, 25, 50, -1 ],
                    [ '10 строк', '25 строк', '50 строк', 'Все записи' ]
                ],
                buttons:[
                    'searchBuilder',
                    'pageLength',
                    {
                        extend: 'colvis',
                        collectionLayout: 'fixed',
                        columnText: function ( dt, idx, title ) {
                            var column_title = $('#orders').DataTable().init().columnDefs[idx].searchBuilderTitle;
                            return title + ': ' + column_title;
                        },
                        postfixButtons: ['colvisRestore'],
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        title: null,
                        sheetName: 'Exported data',
                        autoFilter: true,
                        createEmptyCells: true,
                        exportOptions: {
                            columns: ':visible',
                            format: {
                                header: function ( text, index, node ) {
                                    // вместо порядковых номеров подставляем название столбцов из списка searchBuilderTitle
                                    return $('#orders').DataTable().init().columnDefs[index].searchBuilderTitle;
                                }
                            }
                        },
                        customize: function( xlsx ) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                            $('row:first c', sheet).attr( 's', '42' );
                        }
                    }
                ],
                ajax: '{$ajax_url}',
                processing: true,
                deferRender: true,
                fixedHeader: {
                    header: true,
                    headerOffset: 40,
                },
                scrollX: true,
                select: true,
                columnDefs: [
                    { targets: [0], searchBuilderTitle: 'Код' },
                    { targets: [1], searchBuilderTitle: 'Статус' },
                    { targets: [2], searchBuilderTitle: 'Код заказчика' },
                    { targets: [3], searchBuilderTitle: 'ФИО заказчика' },
                    { targets: [4], searchBuilderTitle: 'Откуда Код страны' },
                    { targets: [5], searchBuilderTitle: 'Откуда Страна' },
                    { targets: [6], searchBuilderTitle: 'Откуда Код города' },
                    { targets: [7], searchBuilderTitle: 'Откуда Город' },
                    { targets: [8], searchBuilderTitle: 'Куда Код страны' },
                    { targets: [9], searchBuilderTitle: 'Куда Страна' },
                    { targets: [10], searchBuilderTitle: 'Куда Код города' },
                    { targets: [11], searchBuilderTitle: 'Куда город' },
                    { targets: [12], searchBuilderTitle: 'Цена' },
                    { targets: [13], searchBuilderTitle: 'Валюта' },
                    { targets: [14], searchBuilderTitle: 'Цена $' },
                    { targets: [15], searchBuilderTitle: 'Цена ком.' },
                    { targets: [16], searchBuilderTitle: 'Кол-во' },
                    { targets: [17], searchBuilderTitle: 'Дата доставки' },
                    { targets: [18], searchBuilderTitle: 'Дата создания' },
                    { targets: [19], searchBuilderTitle: 'Дата изменения' },
                    { targets: [20], searchBuilderTitle: 'Жалобы' },
                ],
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
                    { data: 'deadline', className: 'dt-body-center' },
                    { data: 'created_at', className: 'dt-body-center' },
                    { data: 'updated_at', className: 'dt-body-center' },
                    { data: 'strikes' },
                ],
                order: [[0, 'desc']],
                language: {
                    url: '/vendor/datatables/ru.json'    // взято и подправлено с https://cdn.datatables.net/plug-ins/1.13.4/i18n/ru.json
                },
                initComplete: function () {
                    // возле поля Поиск добавляем кнопку "Очистить поле поиска"
                    $('<button type="button" class="btn-danger"><i class="fa fa-times"></i></button>').appendTo('div.dataTables_filter');

                    // нажата кнопка "Очистить поле поиска"
                    $('.dataTables_filter').on('click', 'button', () =>  table.search('').draw());
                }
            });

            // код ниже выполняем с задержкой, пока загрузятся данные таблицы
            setTimeout(function() {
                // Устанавливаем дефолтный фильтр для таблицы
                table.searchBuilder.rebuild({
                    criteria:[
                        {
                            data: 'Статус',
                            condition: 'contains',
                            value: ['active']
                        },
                        {
                            data: 'Статус',
                            condition: 'contains',
                            value: ['in_work']
                        }
                    ],
                    logic: 'OR'
                });
            }, 1000);

SCRIPT;
        Admin::script($script);
    }
}
