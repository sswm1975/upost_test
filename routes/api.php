<?php

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

const MIDDLEWARE_AUTH_BASIC = 'auth.basic';

# Авторизація
Route::get('login', function () {
    return response()->json([
        'status'  => true,
        'message' => __('message.login_successful'),
    ]);
})->middleware(MIDDLEWARE_AUTH_BASIC);

# Реєстрація
Route::post('register', 'API\RegisterController@register');

// Операції с провілем користувача

# Отримання інформації про користувача (тільки публічні дані)
Route::get('profile/{user_id}', 'API\ProfileController@getPublicData')->middleware(MIDDLEWARE_AUTH_BASIC);

# Отримання інформації про користувача (всі дані)
Route::get('profile', 'API\ProfileController@getPrivateData')->middleware(MIDDLEWARE_AUTH_BASIC);

# Зміна даних профілю (тільки публічні дані)
Route::post('profile', 'API\ProfileController@updatePublicData')->middleware(MIDDLEWARE_AUTH_BASIC);

# Отримання рейтингу користувача
Route::get('rating/{user_id}', 'API\ProfileController@getRating')->middleware(MIDDLEWARE_AUTH_BASIC);


// Відгуки
Route::group(
    [
        'prefix' => 'reviews',
    ],
    function () {
        # Додати відгук
        Route::post('add', 'API\RewiesController@addReview')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримати відгуки
        Route::get('show', 'API\RewiesController@showReviews');
    }
);

// Повiдомлення
Route::group(
    [
        'prefix' => 'messages',
    ],
    function () {
        # Додати відгук
        Route::post('add', 'API\MessagesController@addMessages')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримати відгуки
        Route::get('show', 'API\MessagesController@showMessages');
    }
);


Route::group(
    [
        'prefix' => 'reviews',
    ],
    function () {
        # Додати відгук
        Route::post('add', 'API\RewiesController@addReview')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримати відгуки
        Route::get('show', 'API\RewiesController@showReviews');
    }
);


# Зміна даних мов та валют
Route::post('language', 'API\ProfileController@updateLanguage')->middleware(MIDDLEWARE_AUTH_BASIC);

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
Route::post('save_order', 'API\OrderController@saveOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

# Вивід замовлень
Route::get('show_orders', 'API\OrderController@showOrders')/*->middleware(MIDDLEWARE_AUTH_BASIC)*/
;

# Видалення замовлення
Route::delete('delete_order/{order_id}', 'API\OrderController@deleteOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

# Підбір замовлення для маршруту
Route::get('selection_order/{route_id}', 'API\OrderController@selectionOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

# Лічильник переглядів
Route::post('update_counter', API\CounterController::class);

# Загрузка фото і створення мініатюр
Route::post('upload_photo', 'API\PhotoLoaderController@uploadPhoto')->middleware(MIDDLEWARE_AUTH_BASIC);

# Створення маршруту
Route::post('save_route', 'API\RouteController@saveRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

# Виведення маршруту
Route::get('show_routes', 'API\RouteController@showRoutes')->middleware(MIDDLEWARE_AUTH_BASIC);

# Редагування маршруту
Route::post('update_route/{route_id}', 'API\RouteController@updateRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

# Видалення маршруту
Route::delete('delete_route/{route_id}', 'API\RouteController@deleteRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

# Підбір маршруту для замовлення
Route::get('selection_route/{order_id}', 'API\RouteController@selectionRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

# Додати в список обраних
Route::post('update_favorite', 'API\FavoriteController@updateFavorite')->middleware(MIDDLEWARE_AUTH_BASIC);

# Вивести список обраних
Route::get('show_favorite', 'API\FavoriteController@showFavorites')->middleware(MIDDLEWARE_AUTH_BASIC);

# Зробити ставку
Route::post('create_rate', 'API\RateController@createRate')->middleware(MIDDLEWARE_AUTH_BASIC);

# Редагувати ставку
Route::post('update_rate/{rate_id}', 'API\RateController@updateRate')->middleware(MIDDLEWARE_AUTH_BASIC);

# Видалити ставку
Route::delete('delete_rate/{rate_id}', 'API\RateController@deleteRate')->middleware(MIDDLEWARE_AUTH_BASIC);

# Відхилити ставку
Route::post('reject_rate/{rate_id}', 'API\RateController@rejectRate')->middleware(MIDDLEWARE_AUTH_BASIC);

# Отримати ставки
Route::get('show_rates', 'API\RateController@showRates')->middleware(MIDDLEWARE_AUTH_BASIC);

# Переглянути ставку
Route::get('show_rate/{rate_id}', 'API\RateController@showRate')->middleware(MIDDLEWARE_AUTH_BASIC);

# Прийняти ставку
Route::post('accept_rate/{rate_id}', 'API\RateController@acceptRate')->middleware(MIDDLEWARE_AUTH_BASIC);

# Створення завдання
Route::post('create_job', 'API\JobController@createJob')->middleware(MIDDLEWARE_AUTH_BASIC);
