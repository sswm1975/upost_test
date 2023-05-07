$.fn.dataTable.moment( 'DD.MM.YYYY' );
$.fn.dataTable.moment( 'DD.MM.YYYY' );
$.fn.dataTable.moment( 'DD.MM.YYYY' );

var columns = [
    { data: 'id', className: 'dt-body-center', searchBuilderTitle: 'Код' },
    { data: 'status', searchBuilderTitle: 'Статус' },
    { data: 'problem_id', className: 'dt-body-center', searchBuilderTitle: 'Код проблемы' },
    { data: 'problem_name', searchBuilderTitle: 'Проблема' },
    { data: 'problem_days', className: 'dt-body-center', searchBuilderTitle: 'Кол-во дней' },
    { data: 'manager_id', className: 'dt-body-center', searchBuilderTitle: 'Код путешественника' },
    { data: 'manager_full_name', searchBuilderTitle: 'ФИО путешественника' },
    { data: 'user_id', className: 'dt-body-center', searchBuilderTitle: 'Код путешественника' },
    { data: 'user_full_name', searchBuilderTitle: 'ФИО путешественника' },
    { data: 'respondent_id', className: 'dt-body-center', searchBuilderTitle: 'Код путешественника' },
    { data: 'respondent_full_name', searchBuilderTitle: 'ФИО путешественника' },
    { data: 'deadline', className: 'dt-body-center', searchBuilderTitle: 'Дата доставки' },
    { data: 'created_at', className: 'dt-body-center', searchBuilderTitle: 'Дата создания' },
    { data: 'updated_at', className: 'dt-body-center', searchBuilderTitle: 'Дата изменения' },
];

// список кнопок с операциями, которые доступны для спора
$.fn.dataTable.ext.buttons.appointDispute = {
    text: 'Назначить спор менеджеру',
    className: 'action',
    action: function (e, dt, node, config) {
        $('#appoint_dispute_modal').modal('show');
    }
};
$.fn.dataTable.ext.buttons.inWorkDispute = {
    text: 'Взять в работу',
    className: 'action',
    action: function (e, dt, node, config) {
        $.admin.swal({
            "type": "question",
            "showCancelButton": true,
            "showLoaderOnConfirm": true,
            "confirmButtonText": "Да",
            "cancelButtonText": "Отмена",
            "title": "Вы точно хотите взять в работу выделенные споры?",
            "text": "",
            preConfirm: function (input) {
                send_ajax(undefined, '/platform/disputes/in_work');
            }
        });
    }
};
$.fn.dataTable.ext.buttons.closeDisputeGuiltyPerformer = {
    text: 'Закрыть спор (виноват путешественник)',
    className: 'action',
    action: function (e, dt, node, config) {
        $('#close_dispute_guilty_performer_modal').modal('show');
    }
};
$.fn.dataTable.ext.buttons.closeDisputeGuiltyCustomer = {
    text: 'Закрыть спор (виноват заказчик)',
    className: 'action',
    action: function (e, dt, node, config) {
        $('#close_dispute_guilty_customer_modal').modal('show');
    }
};
$.fn.dataTable.ext.buttons.cancelDispute = {
    text: 'Отменить спор',
    className: 'action',
    action: function (e, dt, node, config) {
        $.admin.swal({
            "type": "question",
            "showCancelButton": true,
            "showLoaderOnConfirm": true,
            "confirmButtonText": "Да",
            "cancelButtonText": "Отмена",
            "title": "Вы точно хотите отменить выделенные споры?",
            "text": "",
            preConfirm: function (input) {
                send_ajax(undefined, '/platform/disputes/canceled');
            }
        });
    }
};

// операции в разрезе статусов
var actions = {
    active: ['appointDispute'],
    appointed: ['inWorkDispute'],
    in_work: ['closeDisputeGuiltyPerformer', 'closeDisputeGuiltyCustomer', 'cancelDispute'],
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
