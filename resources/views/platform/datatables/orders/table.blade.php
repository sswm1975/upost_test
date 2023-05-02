<table id="grid" class="display cell-border compact nowrap" style="width:100%;background-color:white">
    <thead>
        <tr>
            <th rowspan="2">Код</th>
            <th rowspan="2">Статус</th>
            <th colspan="2">Заказчик</th>
            <th colspan="4">Откуда</th>
            <th colspan="4">Куда</th>
            <th colspan="5">Стоимость</th>
            <th colspan="3">Даты</th>
            <th rowspan="2">Наименование</th>
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
            @for($i=1; $i<=$count_columns; $i++)
                <th>{{ $i }}</th>
            @endfor
        </tr>
    </thead>
    <tfoot style="display:none">
        <tr>
            @for($i=1; $i<=$count_columns; $i++)
                <th>
                    <input type="search" data-index="{{ $i-1 }}" size="1" aria-controls="grid" style="width:100%">
                </th>
            @endfor
        </tr>
    </tfoot>
</table>
