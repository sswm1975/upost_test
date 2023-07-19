<table id="grid" class="display cell-border compact nowrap" style="width:100%;background-color:white">
    <thead>
        <tr>
            <th rowspan="2">Код</th>
            <th rowspan="2">Статус</th>
            <th colspan="3">Заказчик</th>
            <th rowspan="2">Сумма</th>
            <th colspan="2">Файл</th>
            <th colspan="2">Даты</th>
        </tr>
        <tr>
            <th>код</th>
            <th>ФИО</th>
            <th>Емейл</th>
            <th>код</th>
            <th>название</th>
            <th>создано</th>
            <th>изменено</th>
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
