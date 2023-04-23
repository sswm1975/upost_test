{{--
Загружается в app\Platform\bootstrap.php
По стилям semanticui смотри https://fomantic-ui.com/collections/table.html
По таблице dataTables смотри https://datatables.net/examples/basic_init/complex_header.html
--}}

<style>
    table.compact>thead>tr>th {background-color:#f9fafb}
    table.compact>thead>tr:first-child>th {border-top:1px solid lightgrey}
    table.compact>thead>tr:nth-child(-n+3)>th:first-child {border-left:1px solid lightgrey}
    table.compact>thead>tr:nth-child(-n+3)>th{border-right:1px solid lightgrey}
    table.compact>thead>tr:nth-child(3)>th{background:lightgrey}
    table.compact>thead>tr:nth-child(-n+2)>th{text-align:center}
    .dataTables_wrapper {font-size:.9em}
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {background-color: white}
    .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_filter label {font-weight:normal}
</style>

    <table id="orders" class="order-column row-border hover cell-border compact nowrap" style="width:100%;background-color:white">
        <thead>
            <tr>
                <th rowspan="2">Код</th>
                <th rowspan="2">Статус</th>
                <th colspan="2">Заказчик</th>
                <th colspan="4">Из</th>
                <th colspan="4">Куда</th>
                <th colspan="5">Стоимость</th>
                <th colspan="3">Даты</th>
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
                <th>к-во</th>
                <th>доставки</th>
                <th>создан</th>
                <th>изменен</th>
            </tr>
            <tr>
                @for($i=1; $i<=21; $i++)
                    <th>{{ $i }}</th>
                @endfor
            </tr>
        </thead>
    </table>
