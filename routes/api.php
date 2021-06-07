<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

# Авторизація
Route::post('login', function () {
    return response()->json([
        'status'  => 200,
        'message' => 'successfully_logged_in',
    ]);
})->middleware('auth.basic');

# Реєстрація
Route::post('register', 'API\RegisterController@register');

# Отримання інформації про користувача (тільки публічні дані)
Route::get('profile/{id}', 'API\ProfileController@getPublicData');

# Отримання інформації про користувача (всі дані)
Route::get('profile', 'API\ProfileController@getPrivateData')->middleware('auth.basic');

# Зміна даних профілю (тільки публічні дані)
Route::post('profile', 'API\ProfileController@updatePublicData')->middleware('auth.basic');

# Дані мов та валют
Route::match(['GET', 'POST'], 'language', API\LanguageController::class)->middleware('auth.basic');

# Отримання даних країни або міста: Отримувати назву країни по її ідентифікатору
Route::match(['GET', 'POST'], 'get_country', 'API\CountryController@getCountry');

# Отримання даних країни або міста: Отримати список всіх країн
Route::match(['GET', 'POST'], 'get_countries', 'API\CountryController@getCountries');

# Отримання даних країни або міста: Отримати назву конкретного міста за його ідентифікатором
Route::match(['GET', 'POST'], 'get_city', 'API\CountryController@getCity');

# Отримання даних країни або міста: Отримати список міст конкретної країни
# Отримання даних країни або міста: Отримати список всіх країн включно зі списком всіх міст конкретної країни
Route::match(['GET', 'POST'], 'get_cities', 'API\CountryController@getCities');

# Отримання категорій: всіх або конкретної категорії
Route::match(['GET', 'POST'], 'get_сategories', 'API\CategoryController@getCategories');

# Створення замовлення
Route::post('save_order', 'API\OrderController@saveOrder')->middleware('auth.basic');

# Вивід замовлень
Route::match(['GET', 'POST'], 'show_orders', 'API\OrderController@showOrders')/*->middleware('auth.basic')*/;

# Видалення замовлення
Route::match(['GET', 'POST'], 'delete_order', 'API\OrderController@deleteOrder')->middleware('auth.basic');

# Лічильник переглядів
Route::match(['GET', 'POST'], 'update_counter', API\CounterController::class);

# Загрузка фото і створення мініатюр
Route::post('upload_photo', 'API\PhotoLoaderController@uploadPhoto')->middleware('auth.basic');

# Створення маршруту
Route::post('save_route', 'API\RouteController@saveRoute')->middleware('auth.basic');

# Виведення маршруту
Route::post('show_routes', 'API\RouteController@showRoutes')->middleware('auth.basic');

# Редагування маршруту
Route::post('update_route/{id}', 'API\RouteController@updateRoute')->middleware('auth.basic');

# Видалення маршруту
Route::delete('delete_route/{id}', 'API\RouteController@deleteRoute')->middleware('auth.basic');
