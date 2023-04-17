{{--
Загружается в app\Platform\bootstrap.php
По стилям semanticui смотри https://fomantic-ui.com/collections/table.html
По таблице dataTables смотри https://datatables.net/examples/basic_init/complex_header.html
--}}
<style>
    .table.compact>thead>tr>th{padding:4px 0}
    .table.compact>thead>tr:nth-child(-n+2)>th{text-align:center}
</style>

<div class="fw-container" style="padding-left: 15px;">
    <table id="orders" class="ui celled structured unstackable selectable compact small scrolling table" style="width:100%">
        <thead>
            <tr>
                <th rowspan="2">Код</th>
                <th rowspan="2">Статус</th>
                <th colspan="2">Заказчик</th>
                <th colspan="4">Из</th>
                <th colspan="4">Куда</th>
                <th colspan="5">Стоимость</th>
                <th colspan="2">Дата доставки</th>
                <th colspan="2">Создан</th>
                <th rowspan="2">Название</th>
                <th rowspan="2">Описание</th>
                <th rowspan="2">Жалобы</th>
            </tr>
            <tr>
                <th>код</th>
                <th>ФИО</th>
                <th>код</th>
                <th>страна</th>
                <th>код</th>
                <th>город</th>
                <th>код</th>
                <th>страна</th>
                <th>код</th>
                <th>город</th>
                <th>цена</th>
                <th>вал</th>
                <th>цена $</th>
                <th>ком. $</th>
                <th>кол-во</th>
                <th>дата</th>
                <th>код</th>
                <th>создан</th>
                <th>изменен</th>
            </tr>
            <tr>
                @for($i=1; $i<=24; $i++)
                    <th style="background:lightgrey">{{ $i }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->status }}</td>
                    <td>{{ $order->user_id }}</td>
                    <td>{{ $order->user_full_name }}</td>
                    <td>{{ $order->from_country_id }}</td>
                    <td>{{ $order->from_country_name }}</td>
                    <td>{{ $order->from_city_id }}</td>
                    <td>{{ $order->from_city_name }}</td>
                    <td>{{ $order->to_country_id }}</td>
                    <td>{{ $order->to_country_name }}</td>
                    <td>{{ $order->to_city_id }}</td>
                    <td>{{ $order->to_city_name }}</td>
                    <td>{{ $order->price }}</td>
                    <td>{{ $order->currency }}</td>
                    <td>{{ $order->price_usd }}</td>
                    <td>{{ $order->user_price_usd }}</td>
                    <td>{{ $order->products_count }}</td>
                    <td>{{ $order->deadline }}</td>
                    <td>{{ $order->wait_range }}</td>
                    <td>{{ $order->created_at }}</td>
                    <td>{{ $order->updated_at }}</td>
                    <td>{{ $order->name }}</td>
                    <td>{!! $order->description !!}</td>
                    <td>{{ $order->strikes }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
