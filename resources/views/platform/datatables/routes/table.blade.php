{{--
Загружается в app\Platform\bootstrap.php
По стилям semanticui смотри https://fomantic-ui.com/collections/table.html
По таблице dataTables смотри https://datatables.net/examples/basic_init/complex_header.html
--}}

<style>
    :focus-visible{outline:none}
    table.compact>thead>tr>th {background-color:#f9fafb}
    table.compact>thead>tr:first-child>th {border-top:1px solid lightgrey}
    table.compact>thead>tr:nth-child(-n+3)>th:first-child {border-left:1px solid lightgrey}
    table.compact>thead>tr:nth-child(-n+3)>th{border-right:1px solid lightgrey}
    table.compact>thead>tr:nth-child(3)>th{background:lightgrey}
    table.compact>thead>tr:nth-child(-n+2)>th{text-align:center}
    .dataTables_wrapper {font-size:.9em}
    .dataTables_wrapper .dataTables_length {margin-left:20px}
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {background-color:white;padding:3px 6px;border-radius:0}
    .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_filter label {font-weight:normal}
    .dataTables_wrapper .dataTables_filter button {padding:3px 7px;border:1px solid transparent}
    .dataTables_wrapper button.dt-button {padding:3px 6px}

    div.dt-buttons .dt-button {border-radius: 0}
    .dataTables_wrapper tbody tr:last-child td {border-bottom: 1px solid rgba(0, 0, 0, 0.15)}
    .dataTables_wrapper button.buttons-footer_toggle,
    .dataTables_wrapper button.buttons-copy {margin-left:-5px}

    /* Стили для плагина "Видимость столбцов", взятл с https://stackoverflow.com/questions/32701006/checkbox-for-each-button-to-select-a-column-in-datatables-1-10-without-colvis */
    .dt-button-collection .dt-button.buttons-columnVisibility {
        background: none !important;
        background-color: transparent !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0.25em 1em !important;
        margin: 0 !important;
        text-align: left !important;
    }
    .dt-button-collection .buttons-columnVisibility:before,
    .dt-button-collection .buttons-columnVisibility.active span:before {
        display:block;
        position:absolute;
        top:1.2em;
        left:0;
        width:12px;
        height:12px;
        box-sizing:border-box;
    }
    .dt-button-collection .buttons-columnVisibility:before {
        content:' ';
        margin-top:-8px;
        margin-left:10px;
        border:1px solid black;
        border-radius:3px;
    }
    .dt-button-collection .buttons-columnVisibility.active span:before {
        content:'\2714';
        margin-top: -11px;
        margin-left: 10px;
        text-align: center;
        text-shadow: 1px 1px #fff, -1px -1px #fff, 1px -1px #fff, -1px 1px #fff;
    }
    .dt-button-collection .buttons-columnVisibility span {
        margin-left:17px;
    }
    /* END Стили для плагина "Видимость столбцов" */
</style>

<table id="orders" class="display cell-border compact nowrap" style="width:100%;background-color:white">
    <thead>
        <tr>
            <th rowspan="2">Код</th>
            <th rowspan="2">Статус</th>
            <th colspan="2">Путешественник</th>
            <th colspan="4">Откуда</th>
            <th colspan="4">Куда</th>
            <th rowspan="2">Кол-во заказов</th>
            <th colspan="2">Стоимость</th>
            <th colspan="3">Даты</th>
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
            <th>цена $</th>
            <th>ком. $</th>
            <th>доставки</th>
            <th>создан</th>
            <th>изменен</th>
        </tr>
        <tr>
            @for($i=1; $i<=18; $i++)
                <th>{{ $i }}</th>
            @endfor
        </tr>
    </thead>
    <tfoot style="display:none">
        <tr>
            @for($i=1; $i<=18; $i++)
                <th>
                    <input type="text" data-index="{{ $i-1 }}" size="1" style="width:100%">
                </th>
            @endfor
        </tr>
    </tfoot>
</table>
