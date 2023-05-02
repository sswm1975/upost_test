// меню со статусами, активируем 1-ый пункт
var menu_statuses = $('ul.nav-statuses');
menu_statuses.find('li:first-child a').addClass('active');

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

var table = $('#grid').DataTable({
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
                return title + ': ' + table.init().columns[index].searchBuilderTitle;
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
                        return table.init().columns[index].searchBuilderTitle;
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
                        return table.init().columns[index].searchBuilderTitle;
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
    ajax: {
        url: ajax_url,
        data: function (params) {
            if (menu_statuses.length) {
                params.status = menu_statuses.find('a.active').data('status');
            }
        },
    },
    processing: true,
    deferRender: true,
    fixedHeader: {
        header: true,
        headerOffset: 50,
    },
    scrollX: true,
    select: {
        info: false
    },
    columns: columns,
    order: [[0, 'desc']],
    stateSave: true,
    language: {
        url: '/vendor/datatables/ru.json' // взято и подправлено с https://cdn.datatables.net/plug-ins/1.13.4/i18n/ru.json
    },
    initComplete: function () {
        // обработчик ввода данных в полях поиска, что находятся в футере таблицы
        $(table.table().container()).on( 'keyup search input paste cut', 'tfoot input', function () {
            table.column( $(this).data('index') ).search( this.value ).draw();
        });

        // в заголовки футера добавляем подсказки с наименованием столбца
        $( table.footer() ).find('th').each(function(index, th) {
            $(th).prop('title', table.init().columns[index].searchBuilderTitle);
        });
    },
});

// клик на пункт меню со статусом
menu_statuses.on('click', 'a', function () {
    menu_statuses.find('a').removeClass('active');
    $(this).addClass('active');
    table.ajax.reload();
});
