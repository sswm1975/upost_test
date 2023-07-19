$.fn.dataTable.moment( 'DD.MM.YYYY' );
$.fn.dataTable.moment( 'DD.MM.YYYY' );

var columns = [
    { data: 'id', className: 'dt-body-center', searchBuilderTitle: 'Код' },
    { data: 'status', searchBuilderTitle: 'Статус' },
    { data: 'user_id', className: 'dt-body-center', searchBuilderTitle: 'Код заказчика' },
    { data: 'user_full_name', searchBuilderTitle: 'ФИО заказчика' },
    { data: 'email', className: 'dt-body-left', searchBuilderTitle: 'Ел.почта' },
    { data: 'amount', className: 'dt-body-right', searchBuilderTitle: 'Сумма' },
    { data: 'file_id', className: 'dt-body-right', searchBuilderTitle: 'Код файла' },
    {
        data: null,
        render: {
            _: 'file_name',
            filter: (row) => row.file_name,
            display: function (row) {
                return `<a href="/platform/withdrawals/download_csv/${row.file_id}" download="${row.file_name}" type="text/csv" target="_blank">${row.file_name}</a>`;
            }
        },
        searchBuilderTitle: 'Наименование файла',
    },
    { data: 'created_at', className: 'dt-body-center', searchBuilderTitle: 'Создано' },
    { data: 'updated_at', className: 'dt-body-center', searchBuilderTitle: 'Изменено' },
];

$.fn.dataTable.ext.buttons.createCSVFile = {
    text: 'Создать CSV файл',
    className: 'action',
    action: function (e, dt, node, config) {
        $.admin.swal({
            "type": "question",
            "showCancelButton": true,
            "showLoaderOnConfirm": true,
            "confirmButtonText": "Да",
            "cancelButtonText": "Отмена",
            "title": "Сформировать  CSV-файл на вывод денег?",
            "text": "",
            preConfirm: function (input) {
                send_ajax(undefined, '/platform/withdrawals/create_csv');
            }
        });

    }
};

// операции в разрезе статусов
var actions = {
    new: ['createCSVFile'],
};
