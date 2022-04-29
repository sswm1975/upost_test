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
    $router->get('clients', 'ClientController@index')->name('clients.index');
    $router->get('clients/{id}', 'ClientController@show')->name('clients.show');

    # Заказы
    $router->get('orders', 'OrderController@index')->name('orders.index');
    $router->get('orders/{id}', 'OrderController@show')->name('orders.show');

    # Маршруты
    $router->get('routes', 'RouteController@index')->name('routes.index');
    $router->get('routes/{id}', 'RouteController@show')->name('routes.show');

    # Споры
    $router->resource('disputes', 'DisputeController', ['except' => ['delete']])->names('disputes');

    # Чаты
    $router->get('chats', 'ChatController@index')->name('chats.index');

    # Обратная связь
    $router->get('feedback', 'FeedbackController@index')->name('feedback.index');
    $router->get('feedback/{id}', 'FeedbackController@show')->name('feedback.show');
    $router->post('feedback/set_read', 'FeedbackController@setRead')->name('feedback.set_read');

    # Рассылки
    $router->get('mailings/{name}', 'MailingController@index')->name('mailings.index');

    # Настройки
    $router->resource('settings', 'SettingController')->names('settings')->middleware('admin.permission:check,settings');

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

        # Проблемы
        $router->resource('problems', 'ProblemController')->names('problems');
    });

    # Пункты меню "Админка"
    $router->group([
        'prefix'     => 'auth',
        'namespace'  => 'Auth',
        'middleware' => 'admin.permission:allow,administrator',
        'as'         => 'auth.',
    ], function (Router $router) {
        $router->get('info', 'InfoController@index')->name('info');
    });
});
