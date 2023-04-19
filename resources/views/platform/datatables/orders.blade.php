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
                <th rowspan="3" style="width:10px;padding:0;background:#ecf0f5;border-top:1px solid #ecf0f5;border-left: 1px solid #ecf0f5">&nbsp;</th>
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
                    <th style="background:lightgrey">{{ $i }}</th>
                @endfor
            </tr>
        </thead>
    </table>
</div>
