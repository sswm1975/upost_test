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
    $router->get('orders', 'OrderController@index')->name('orders.index')->middleware('admin.permission:check,orders');
    $router->get('orders/{id}', 'OrderController@show')->name('orders.show')->middleware('admin.permission:check,orders');

    # Маршруты
    $router->get('routes', 'RouteController@index')->name('routes.index')->middleware('admin.permission:check,routes');
    $router->get('routes/{id}', 'RouteController@show')->name('routes.show')->middleware('admin.permission:check,routes');

    # Платежи (Заявки на выплату)
    $router->resource('payments', 'PaymentController', ['except' => ['delete']])->names('payments')->middleware('admin.permission:check,payments');

    # Споры
    $router->resource('disputes', 'DisputeController', ['except' => ['delete']])->names('disputes')->middleware('admin.permission:check,disputes');

    # Чаты
    $router->get('chats', 'ChatController@index')->name('chats.index')->middleware('admin.permission:check,chats');
    $router->post('chats/add_message', 'ChatController@addMessage')->name('chats.add_message')->middleware('admin.permission:check,chats');
    $router->put('chats/{chat}', 'ChatController@update')->name('chats.update')->middleware('admin.permission:check,chats');

    # Обратная связь
    $router->get('feedback', 'FeedbackController@index')->name('feedback.index');
    $router->get('feedback/{id}', 'FeedbackController@show')->name('feedback.show');
    $router->post('feedback/set_read', 'FeedbackController@setRead')->name('feedback.set_read');

    # Рассылки
    $router->get('mailings/{name}', 'MailingController@index')->name('mailings.index');

    # Настройки
    $router->resource('settings', 'SettingController')->names('settings')->middleware('admin.permission:check,settings');

    # Скрипты
    $router->resource('admin/scripts', 'Admin\ScriptController')->middleware('admin.permission:allow,administrator');
    $router->post('admin/scripts/run', 'Admin\ScriptController@runScript')->name('scripts.run')->middleware('admin.permission:allow,administrator');

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


    });
});
