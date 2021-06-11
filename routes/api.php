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
Route::get('login', function () {
    return response()->json([
        'status'  => 200,
        'message' => 'successfully_logged_in',
    ]);
})->middleware('auth.basic');

# Реєстрація
Route::post('register', 'API\RegisterController@register');

# Отримання інформації про користувача (тільки публічні дані)
Route::get('profile/{user_id}', 'API\ProfileController@getPublicData');

# Отримання інформації про користувача (всі дані)
Route::get('profile', 'API\ProfileController@getPrivateData')->middleware('auth.basic');

# Зміна даних профілю (тільки публічні дані)
Route::post('profile', 'API\ProfileController@updatePublicData')->middleware('auth.basic');

# Зміна даних мов та валют
Route::post('language', API\LanguageController::class)->middleware('auth.basic');

# Отримувати назву країни по її ідентифікатору
Route::get('get_country/{country_id}', 'API\CountryController@getCountry');

# Отримати список всіх країн
Route::get('get_countries', 'API\CountryController@getCountries');

# Отримати назву міста за його ідентифікатором
Route::get('get_city/{city_id}', 'API\CountryController@getCity');

# Отримати список міст по всім країнам або конкретної країни
Route::get('get_cities/{country_id?}', 'API\CountryController@getCities');

# Отримання всіх або конкретної категорії
Route::get('get_сategories/{category_id?}', 'API\CategoryController@getCategories');

# Створення замовлення
Route::post('save_order', 'API\OrderController@saveOrder')->middleware('auth.basic');

# Вивід замовлень
Route::get('show_orders', 'API\OrderController@showOrders')/*->middleware('auth.basic')*/;

# Видалення замовлення
Route::delete('delete_order/{order_id}', 'API\OrderController@deleteOrder')->middleware('auth.basic');

# Підбір замовлення для маршруту
Route::get('selection_order/{route_id}', 'API\OrderController@selectionOrder')->middleware('auth.basic');

# Лічильник переглядів
Route::post('update_counter', API\CounterController::class);

# Загрузка фото і створення мініатюр
Route::post('upload_photo', 'API\PhotoLoaderController@uploadPhoto')->middleware('auth.basic');

# Створення маршруту
Route::post('save_route', 'API\RouteController@saveRoute')->middleware('auth.basic');

# Виведення маршруту
Route::get('show_routes', 'API\RouteController@showRoutes')->middleware('auth.basic');

# Редагування маршруту
Route::post('update_route/{route_id}', 'API\RouteController@updateRoute')->middleware('auth.basic');

# Видалення маршруту
Route::delete('delete_route/{route_id}', 'API\RouteController@deleteRoute')->middleware('auth.basic');

# Підбір маршруту для замовлення
Route::get('selection_route/{order_id}', 'API\RouteController@selectionRoute')->middleware('auth.basic');

# Додати в список обраних
Route::post('update_favorite', 'API\FavoriteController@updateFavorite')->middleware('auth.basic');
