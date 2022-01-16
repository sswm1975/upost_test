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

    $router->group(['as' => 'dashboard.'], function ($router) {
        $router->get('/', 'DashboardController@index')->name('index');
    });

    # Клиенты
    $router->get('clients', 'ClientController@index')->name('clients.index');
    $router->get('clients/{id}', 'ClientController@show')->name('clients.show');

    # Настройки
    $router->resource('settings', 'SettingController')->names('settings')->middleware('admin.permission:check,settings');

    # Справочники
    $router->group([
        'prefix'     => 'handbooks',
        'namespace'  => 'Handbooks',
        'as'         => 'handbooks.',
        'middleware' => 'admin.permission:check,handbooks',
    ], function ($router) {
        # Диапазоны ожидания
        $router->resource('wait_range', 'WaitRangeController')->names('wait_range');
    });

    # Пункты меню "Админка"
    $router->group([
        'prefix'     => 'auth',
        'namespace'  => 'Auth',
        'middleware' => 'admin.permission:allow,administrator',
        'as' => 'auth.',
    ], function (Router $router) {
        $router->get('info', 'InfoController@index')->name('info');
    });
});
