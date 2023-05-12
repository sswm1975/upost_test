<table id="grid" class="display cell-border compact nowrap" style="width:100%;background-color:white">
    <thead>
        <tr>
            <th rowspan="2">Код</th>
            <th colspan="3">Чат</th>
            <th colspan="2">Заказчик</th>
            <th colspan="2">Путешественник</th>
            <th colspan="2">Заказ</th>
            <th colspan="2">Маршрут</th>
        </tr>
        <tr>
            <th>статус</th>
            <th>блокировка</th>
            <th>создан</th>
            <th>код</th>
            <th>фио</th>
            <th>код</th>
            <th>фио</th>
            <th>код</th>
            <th>статус</th>
            <th>код</th>
            <th>статус</th>
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
