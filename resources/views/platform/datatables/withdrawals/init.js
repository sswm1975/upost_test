$.fn.dataTable.moment( 'DD.MM.YYYY' );
$.fn.dataTable.moment( 'DD.MM.YYYY' );

var columns = [
    { data: 'id', className: 'dt-body-center', searchBuilderTitle: 'Код' },
    { data: 'status', searchBuilderTitle: 'Статус' },
    { data: 'user_id', className: 'dt-body-center', searchBuilderTitle: 'Код заказчика' },
    { data: 'user_full_name', searchBuilderTitle: 'ФИО заказчика' },
    { data: 'email', className: 'dt-body-center', searchBuilderTitle: 'Ел.почта' },
    { data: 'amount', className: 'dt-body-right', searchBuilderTitle: 'Сумма' },
    { data: 'created_at', className: 'dt-body-center', searchBuilderTitle: 'Создано' },
    { data: 'updated_at', className: 'dt-body-center', searchBuilderTitle: 'Изменено' },
];
