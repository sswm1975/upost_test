<?php

use Illuminate\Routing\Router;

Admin::routes();

# Основные пункты меню
Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
    'as'         => config('admin.route.prefix') . '.',
], function (Router $router) {

    # Доска
    $router->get('/', 'DashboardController@index')->name('dashboard.index');

    # Клиенты
    $router->get('clients', 'ClientController@index')->name('clients.index')->middleware('admin.permission:check,clients');
    $router->get('clients/{id}', 'ClientController@show')->name('clients.show')->middleware('admin.permission:check,clients');;

    # Заказы
    $router->get('orders', 'DataTables\OrderController@index')->name('orders.index')->middleware('admin.permission:check,orders');
    $router->get('ajax/orders', 'DataTables\OrderController@getData')->name('ajax.orders')->middleware('admin.permission:check,orders');

    # Маршруты
    $router->get('routes', 'DataTables\RouteController@index')->name('routes.index')->middleware('admin.permission:check,routes');
    $router->get('ajax/routes', 'DataTables\RouteController@getData')->name('ajax.routes')->middleware('admin.permission:check,routes');

    # Заявки на вивід грошей
    $router->get('withdrawals', 'DataTables\WithdrawalsController@index')->name('withdrawals.index')->middleware('admin.permission:check,withdrawals');
    $router->get('ajax/withdrawals', 'DataTables\WithdrawalsController@getData')->name('ajax.withdrawals')->middleware('admin.permission:check,withdrawals');
    $router->post('withdrawals/create_csv', 'DataTables\WithdrawalsController@createCSVFile')->name('withdrawals.create_csv')->middleware('admin.permission:check,withdrawals');
    $router->get('withdrawals/download_csv/{id}', 'DataTables\WithdrawalsController@downloadCSVFile')->name('withdrawals.download_csv')->middleware('admin.permission:check,withdrawals');

    # Споры
    $router->get('disputes', 'DataTables\DisputeController@index')->name('disputes.index')->middleware('admin.permission:check,disputes');
    $router->get('ajax/disputes', 'DataTables\DisputeController@getData')->name('ajax.disputes')->middleware('admin.permission:check,disputes');
    $router->post('disputes/appoint', 'DataTables\DisputeController@appointDispute')->middleware('admin.permission:check,disputes');
    $router->post('disputes/in_work', 'DataTables\DisputeController@inWorkDispute')->middleware('admin.permission:check,disputes');
    $router->post('disputes/close/guilty_performer', 'DataTables\DisputeController@closeDisputeGuiltyPerformer')->middleware('admin.permission:check,disputes');
    $router->post('disputes/close/guilty_customer', 'DataTables\DisputeController@closeDisputeGuiltyCustomer')->middleware('admin.permission:check,disputes');
    $router->post('disputes/canceled', 'DataTables\DisputeController@canceledDispute')->middleware('admin.permission:check,disputes');
    $router->post('disputes/set_chat_lock_status', 'DataTables\DisputeController@setChatLockStatus')->middleware('admin.permission:check,disputes');

    # Чаты
    $router->get('chats', 'DataTables\ChatController@index')->name('chats.index')->middleware('admin.permission:check,chats');
    $router->get('ajax/chats', 'DataTables\ChatController@getData')->name('ajax.chats')->middleware('admin.permission:check,chats');
    $router->post('chats/set_chat_lock_status', 'DataTables\ChatController@setChatLockStatus')->middleware('admin.permission:check,chats');

    # Платежи (Заявки на выплату)
    $router->resource('payments', 'PaymentController', ['except' => ['delete']])->names('payments')->middleware('admin.permission:check,payments');

    # Транзакции
    $router->get('transactions', 'TransactionController@index')->name('transactions.index')->middleware('admin.permission:allow,administrator');

    # Треки доставки
    $router->resource('tracks', 'TrackController')->names('tracks')->middleware('admin.permission:allow,administrator');

    # Обратная связь
    $router->get('feedback', 'FeedbackController@index')->name('feedback.index');
    $router->get('feedback/{id}', 'FeedbackController@show')->name('feedback.show');
    $router->post('feedback/set_read', 'FeedbackController@setRead')->name('feedback.set_read');

    # Рассылки
    $router->get('mailings/{name}', 'MailingController@index')->name('mailings.index');

    # Графики
    $router->group([
        'prefix'     => 'charts',
        'namespace'  => 'Charts',
        'as'         => 'charts.',
//        'middleware' => 'admin.permission:check,handbooks',
    ], function ($router) {
        $router->get('routes', 'RoutesController@index');
        $router->get('orders', 'OrdersController@index');
    });

    # Справочники
    $router->group([
        'prefix'     => 'handbooks',
        'namespace'  => 'Handbooks',
        'as'         => 'handbooks.',
        'middleware' => 'admin.permission:check,handbooks',
    ], function ($router) {
        # Магазины
        $router->resource('shops', 'ShopController')->names('shops');

        # Диапазоны ожидания
        $router->resource('wait_ranges', 'WaitRangeController')->names('wait_ranges');

        # Жалобы
        $router->resource('complaints', 'ComplaintController')->names('complaints');

        # Проблемы спора
        $router->resource('dispute_problems', 'DisputeProblemController')->names('dispute_problems');

        # Причины закрытия спора
        $router->resource('dispute_closed_reasons', 'DisputeClosedReasonController')->names('dispute_closed_reasons');

        # Курсы валют
        $router->get('currencies', 'CurrenciesController@index');

        # Типы уведомлений
        $router->resource('notice_types', 'NoticeTypeController')->except(['delete'])->names('notice_types');

        # Страны
        $router->resource('countries', 'CountryController')->except(['delete'])->names('countries');

        # Податок з продажу в США
        $router->resource('us_sales_tax', 'UsSalesTaxController')->names('us_sales_tax');
    });

    # Пункты меню "Настройки"
    $router->group([
        'prefix'     => 'settings',
        'namespace'  => 'Settings',
        'middleware' => 'admin.permission:allow,administrator',
        'as'         => 'settings.',
    ], function (Router $router) {
        # Константы
        $router->resource('constants', 'ConstantController')->names('constants');

        # Налоги
        $router->resource('taxes', 'TaxController')->except(['delete'])->names('taxes');
        $router->post('taxes/run_script', 'TaxController@runScript')->name('taxes.run_script');
    });

    # Пункты меню "Админка"
    $router->group([
        'prefix'     => 'admin',
        'namespace'  => 'Admin',
        'middleware' => 'admin.permission:allow,administrator',
        'as'         => 'admin.',
    ], function (Router $router) {
        # Уведомления
        $router->get('notices', 'NoticeController@index')->name('notices');

        # Сервисные уведомления
        $router->resource('service_notices', 'ServiceNoticeController')->except(['delete'])->names('service_notices');
    });

    # Пункты меню "Админка"
    $router->group([
        'prefix'     => 'auth',
        'namespace'  => 'Auth',
        'middleware' => 'admin.permission:allow,administrator',
        'as'         => 'auth.',
    ], function (Router $router) {
        # Информация о проекте
        $router->get('info', 'InfoController@index')->name('info');

        # Пользователи (вместо дефолтного Encore\Admin\Controllers\UserController)
        $router->resource('users', 'UserController')->names('admin.auth.users');

        # Журнал API-запросов
        $router->get('api_request_logging', 'ApiRequestLoggingController@index')->name('api_request_logging.index');
        $router->get('api_request_logging/toggle', 'ApiRequestLoggingController@toggleLog')->name('api_request_logging.toggle');
        $router->get('api_request_logging/truncate', 'ApiRequestLoggingController@truncateLog')->name('api_request_logging.truncate');
    });

    # Пункты меню OLD
    $router->group([
        'prefix'     => 'old',
        'namespace'  => 'Old',
        'middleware' => 'admin.permission:allow,administrator',
        'as'         => 'old.',
    ], function (Router $router) {
        # Заказы
        $router->get('orders', 'OrderController@index')->name('orders.index')->middleware('admin.permission:check,orders');
        $router->get('orders/{id}', 'OrderController@show')->name('orders.show')->middleware('admin.permission:check,orders');

        # Маршруты
        $router->get('routes', 'RouteController@index')->name('routes.index')->middleware('admin.permission:check,routes');
        $router->get('routes/{id}', 'RouteController@show')->name('routes.show')->middleware('admin.permission:check,routes');

        # Споры
        $router->get('disputes/counter', 'DisputeController@getDisputesCounter')->name('disputes.counter')->middleware('admin.permission:check,disputes'); # Получить количество споров по фильтру
        $router->resource('disputes', 'DisputeController', ['except' => ['delete']])->names('disputes')->middleware('admin.permission:check,disputes');
        $router->get('disputes/{chat_id}/clear_unread_messages_count', 'DisputeController@clearUnreadMessagesCount')->name('disputes.clear_unread_messages_count')->middleware('admin.permission:check,disputes');

        # Чаты
        $router->get('chats', 'ChatController@index')->name('chats.index')->middleware('admin.permission:check,chats');
        $router->post('chats/add_message', 'ChatController@addMessage')->name('chats.add_message')->middleware('admin.permission:check,chats');
        $router->put('chats/{chat}', 'ChatController@update')->name('chats.update')->middleware('admin.permission:check,chats');
    });
});
