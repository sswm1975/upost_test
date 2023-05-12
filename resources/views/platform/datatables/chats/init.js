$.fn.dataTable.moment( 'DD.MM.YYYY' );

var columns = [
    { data: 'id', className: 'dt-body-center', searchBuilderTitle: 'Код' },
    { data: 'status', searchBuilderTitle: 'Статус' },
    { data: 'lock_status', searchBuilderTitle: 'Блокировка' },
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

// отправить аякс-запрос
function send_ajax(form, url)
{
    var data = new FormData(form);
    data.append('_token', $.admin.token);
    data.append('ids', JSON.stringify(table.rows( { selected: true } ).data().pluck('id').toArray()));

    $.ajax({
        method: 'POST',
        url: form === undefined ? url : form.action,
        data: data,
        cache: false,
        contentType: false,
        processData: false,
        success: function (data) {
            if (data.status) {
                $(form).closest('.modal').modal('hide');
                table.ajax.reload();
                toastr.success(data.message);
            } else {
                toastr.warning(data.message, 'Внимание');
            }
        },
        error:function(request){
            console.log(request);
            toastr.error('Детализация в console.log', 'Ошибка');
        }
    });
}

// обработчик модалок
$('.modal form').off('submit').on('submit', function (e) {
    e.preventDefault();
    send_ajax(this);
});
