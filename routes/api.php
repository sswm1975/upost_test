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

// Операції с профілем користувача
Route::group(
    [
        'prefix' => 'users',
    ],
    function () {
        # Авторизація
        Route::get('login', function () {
            return response()->json([
                'status'  => true,
                'message' => __('message.login_successful'),
            ]);
        })->middleware(MIDDLEWARE_AUTH_BASIC);

        # Реєстрація
        Route::post('register', 'API\RegisterController@register');

        # Отримання інформації про користувача (тільки публічні дані)
        Route::get('{user_id}/profile', 'API\ProfileController@getPublicData')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримання інформації про користувача (всі дані)
        Route::get('profile', 'API\ProfileController@getPrivateData')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна даних профілю (тільки публічні дані)
        Route::post('profile/update', 'API\ProfileController@updatePublicData')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна даних мов та валют
        Route::post('language/update', 'API\ProfileController@updateLanguage')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна паролю
        Route::post('password/update', 'API\ProfileController@updatePassword')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна логіну: телефону та/або емейлу
        Route::post('login/update', 'API\ProfileController@updateLogin')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна даних пластикової картки
        Route::post('card/update', 'API\ProfileController@updateCard')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Верифікація зміни даних користувача (тільки пароль/логін/картка)
        Route::get('verification/{token}', 'API\ProfileController@verificationUser');
    }
);

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

        # Отримати рейтинг користувача
        Route::get('rating', 'API\RewiesController@getRating');
    }
);

// Чати
Route::group(
    [
        'prefix' => 'chats',
    ],
    function () {
        # Додати чат
        Route::post('add', 'API\ChatsController@addChat')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримати чат
        Route::get('show', 'API\ChatsController@showChats')->middleware(MIDDLEWARE_AUTH_BASIC);;

        # Видалити чат
        Route::post('delete', 'API\ChatsController@deleteChat')->middleware(MIDDLEWARE_AUTH_BASIC);
    }
);

// Повідомлення
Route::group(
    [
        'prefix' => 'messages',
    ],
    function () {
        # Додати повiдомлення
        Route::post('add', 'API\MessagesController@addMessage')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримати повiдомлення1
        Route::get('show', 'API\MessagesController@showMessages')->middleware(MIDDLEWARE_AUTH_BASIC);
    }
);

// Країна і Місто
Route::group(
    [
        'prefix' => 'countries',
    ],
    function () {
        # Отримати список всіх країн
        Route::get('show', 'API\CountryController@getCountries');

        # Отримати список міст по всім країнам
        Route::get('cities/show', 'API\CountryController@getCities');

        # Отримувати назву країни по її ідентифікатору
        Route::get('{country_id}/show', 'API\CountryController@getCountry');

        # Отримати назву міста за його ідентифікатором
        Route::get('cities/{city_id}/show', 'API\CountryController@getCity');

        # Отримати список міст по всім країнам або конкретної країни
        Route::get('{country_id}/cities/show', 'API\CountryController@getCities');
    }
);

// Категорії
Route::group(
    [
        'prefix' => 'сategories',
    ],
    function () {
        # Отримання всіх категорій
        Route::get('show', 'API\CategoryController@getCategories');

        # Отримання конкретної категорії
        Route::get('{category_id}/show', 'API\CategoryController@getCategories');
    }
);

// Замовлення
Route::group(
    [
        'prefix' => 'orders',
    ],
    function () {
        # Створення замовлення
        Route::post('add', 'API\OrderController@addOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід замовлень
        Route::get('show', 'API\OrderController@showOrders');

        # Видалення замовлення
        Route::delete('{order_id}/delete', 'API\OrderController@deleteOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Підбір маршруту для замовлення
        Route::get('{order_id}/selection_route', 'API\OrderController@selectionRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Підтвердити виконання замовлення
        Route::post('confirm', 'API\OrderController@confirmOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Скарга на замовлення
        Route::post('{order_id}/strike', 'API\OrderController@strikeOrder')->middleware(MIDDLEWARE_AUTH_BASIC);
    }
);

# Лічильник переглядів
Route::post('update_counter', API\CounterController::class);

# Загрузка фото і створення мініатюр
Route::post('upload_photo', 'API\PhotoLoaderController@uploadPhoto')->middleware(MIDDLEWARE_AUTH_BASIC);

// Маршрут
Route::group(
    [
        'prefix' => 'routes',
    ],
    function () {
        # Створення маршруту
        Route::post('add', 'API\RouteController@addRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Виведення маршруту
        Route::get('show', 'API\RouteController@showRoutes')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Редагування маршруту
        Route::post('{route_id}/update', 'API\RouteController@updateRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Видалення маршруту
        Route::delete('{route_id}/delete', 'API\RouteController@deleteRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Підбір замовлення для маршруту
        Route::get('{route_id}/selection_order', 'API\RouteController@selectionOrder')->middleware(MIDDLEWARE_AUTH_BASIC);
    }
);

// Список обраних
Route::group(
    [
        'prefix' => 'favorites',
    ],
    function () {
        # Додати в список обраних
        Route::post('update', 'API\FavoriteController@updateFavorite')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивести список обраних
        Route::get('show', 'API\FavoriteController@showFavorites')->middleware(MIDDLEWARE_AUTH_BASIC);
    }
);

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

# Парсинг даних
Route::get('parser', API\ParserController::class)->middleware(MIDDLEWARE_AUTH_BASIC);

# Завантаження файлу
Route::post('upload', 'API\UploadController@upload')->middleware(MIDDLEWARE_AUTH_BASIC);

// Спори
Route::group(
    [
        'prefix' => 'disputes',
    ],
    function () {
        # Відкрити спор на завдання
        Route::post('add', 'API\DisputeController@addDispute')->middleware(MIDDLEWARE_AUTH_BASIC);
    }
);
