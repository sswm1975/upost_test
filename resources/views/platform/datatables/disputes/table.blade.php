<table id="grid" class="display cell-border compact nowrap" style="width:100%;background-color:white">
    <thead>
        <tr>
            <th rowspan="2">Код</th>
            <th rowspan="2">Статус</th>
            <th colspan="3">Диспут</th>
            <th colspan="2">Менеджер</th>
            <th colspan="2">Инициатор</th>
            <th colspan="2">Ответчик</th>
            <th rowspan="2">Статус<br>блокировки чата</th>
            <th colspan="3">Даты</th>
        </tr>
        <tr>
            <th>код</th>
            <th>проблема</th>
            <th>дней</th>
            <th>код</th>
            <th>фио</th>
            <th>код</th>
            <th>фио</th>
            <th>код</th>
            <th>фио</th>
            <th>дедлайна</th>
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
