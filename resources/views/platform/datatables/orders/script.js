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

$.fn.dataTable.ext.buttons.footer_toggle = {
    text: '<i class="fa fa-caret-square-o-down"></i>',
    className: 'bg-light-blue buttons-footer_toggle',
    action: function ( e, dt, node, config ) {
        config.showFooter = !config.showFooter;
        this.text( config.showFooter ? '<i class="fa fa-caret-square-o-up"></i>' : '<i class="fa fa-caret-square-o-down"></i>' );
        $( dt.footer() ).toggle();
        dt.draw();
    },
    showFooter: false,
};

var table = $('#orders').DataTable({
    dom: 'Bfrtip',
    lengthMenu: [
        [ 10, 20, 50, 100, -1 ],
        [ '10 строк', '20 строк', '50 строк', '100 строк', 'Все записи' ]
    ],
    buttons:[
        {
            extend: 'searchBuilder',
            className: 'bg-blue',
        },
        'footer_toggle',
        {
            extend: 'pageLength',
            className: 'bg-orange',
        },
        {
            extend: 'colvis',
            text: '<i class="fa fa-table"></i>',
            titleAttr: 'Видимость столбцов',
            className: 'bg-green',
            columnText: function ( dt, index, title ) {
                // дополняем порядковые номера названием столбцов из списка searchBuilderTitle
                return title + ': ' + table.init().columnDefs[index].searchBuilderTitle;
            },
            postfixButtons: ['colvisRestore'],
        },
        {
            extend: 'excelHtml5',
            text: '<i class="fa fa-file-excel-o"></i>',
            titleAttr: 'Экспорт в Excel',
            className: 'bg-maroon',
            title: null,
            sheetName: 'Exported data',
            autoFilter: true,
            createEmptyCells: true,
            exportOptions: {
                columns: ':visible',
                format: {
                    header: function ( text, index, node ) {
                        // вместо порядковых номеров подставляем название столбцов из списка searchBuilderTitle
                        return table.init().columnDefs[index].searchBuilderTitle;
                    }
                }
            },
            customize: function( xlsx ) {
                var sheet = xlsx.xl.worksheets['sheet1.xml'];
                $('row:first c', sheet).attr( 's', '32' );
            }
        },
        {
            extend: 'copyHtml5',
            text: '<i class="fa fa-files-o"></i>',
            titleAttr: 'Копировать в буфер',
            className: 'bg-fuchsia',
            title: null,
            exportOptions: {
                columns: ':visible',
                format: {
                    header: function ( text, index, node ) {
                        // вместо порядковых номеров подставляем название столбцов из списка searchBuilderTitle
                        return table.init().columnDefs[index].searchBuilderTitle;
                    }
                }
            },
        },
        {
            text: '<i class="fa fa-refresh"></i>',
            titleAttr: 'Обновить данные',
            action: function ( e, dt, node, config ) {
                dt.ajax.reload();
            }
        },
    ],
    ajax: '$ajax_url',
    processing: true,
    deferRender: true,
    fixedHeader: {
        header: true,
        headerOffset: 40,
    },
    scrollX: true,
    select: {
        info: false
    },
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
    stateSave: true,
    language: {
        url: '/vendor/datatables/ru.json'    // взято и подправлено с https://cdn.datatables.net/plug-ins/1.13.4/i18n/ru.json
    },
    initComplete: function () {
        // возле поля Поиск добавляем кнопку "Очистить поле поиска"
        $('<button type="button" class="btn-danger"><i class="fa fa-times"></i></button>').appendTo('div.dataTables_filter');

        // нажата кнопка "Очистить поле поиска"
        $('.dataTables_filter').on('click', 'button', () =>  table.search('').draw());

        // Filter event handler
        $(table.table().container()).on( 'keyup', 'tfoot input', function () {
            table.column( $(this).data('index') ).search( this.value ).draw();
        });

        // в заголовки футера добавляем подсказки с наименованием столбца
        $( table.footer() ).find('th').each(function(index, th) {
            $(th).prop('title', table.init().columnDefs[index].searchBuilderTitle);
        });
    },
});
