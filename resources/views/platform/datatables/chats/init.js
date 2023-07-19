$.fn.dataTable.moment( 'DD.MM.YYYY' );

var columns = [
    { data: 'id', className: 'dt-body-center', searchBuilderTitle: 'Код' },
    { data: 'status', searchBuilderTitle: 'Статус' },
    { data: 'lock_status', searchBuilderTitle: 'Блокировка' },
    { data: 'dispute_admin_user_name', searchBuilderTitle: 'Админ' },
    { data: 'created_at', className: 'dt-body-center', searchBuilderTitle: 'Дата создания' },
    { data: 'customer_id', className: 'dt-body-center', searchBuilderTitle: 'Код заказчика' },
    { data: 'customer_full_name', searchBuilderTitle: 'ФИО заказчика' },
    { data: 'performer_id', className: 'dt-body-center', searchBuilderTitle: 'Код исполнителя' },
    { data: 'performer_full_name', searchBuilderTitle: 'ФИО исполнителя' },
    { data: 'order_id', className: 'dt-body-center', searchBuilderTitle: 'Код заказа' },
    { data: 'order_status', searchBuilderTitle: 'Статус заказа' },
    { data: 'route_id', className: 'dt-body-center', searchBuilderTitle: 'Код маршрута' },
    { data: 'route_status', searchBuilderTitle: 'Статус маршрута' },
];

$.fn.dataTable.ext.buttons.setChatLockStatus = {
    text: 'Установить статус блокировки для чата',
    className: 'action',
    action: function (e, dt, node, config) {
        $('#chat_lock_status_modal').modal('show');
    }
};

// операции в разрезе статусов
var actions = {
    active: ['setChatLockStatus'],
};
