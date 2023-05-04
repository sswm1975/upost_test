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
        alert('Test1');
    }
};
$.fn.dataTable.ext.buttons.inWorkDispute = {
    text: 'Взять в работу',
    className: 'action',
    action: function (e, dt, node, config) {
        alert('Test1');
    }
};
$.fn.dataTable.ext.buttons.closeDisputeGuiltyPerformer = {
    text: 'Закрыть спор (виноват путешественник)',
    className: 'action',
    action: function (e, dt, node, config) {
        alert('Test2');
    }
};
$.fn.dataTable.ext.buttons.closeDisputeGuiltyCustomer = {
    text: 'Закрыть спор (виноват заказчик)',
    className: 'action',
    action: function (e, dt, node, config) {
        alert('Test3');
    }
};
$.fn.dataTable.ext.buttons.cancelDispute = {
    text: 'Отменить спор',
    className: 'action',
    action: function (e, dt, node, config) {
        alert('Test3');
    }
};

// операции в разрезе статусов
var actions = {
    active: ['appointDispute'],
    appointed: ['inWorkDispute'],
    in_work: ['closeDisputeGuiltyPerformer', 'closeDisputeGuiltyCustomer', 'cancelDispute'],
};



