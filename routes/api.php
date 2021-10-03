<?php

use Illuminate\Support\Facades\Route;

const MIDDLEWARE_AUTH_BASIC = 'auth:api';

// Аутентифікація
Route::group(
    [
        'prefix' => 'auth',
    ],
    function () {
        # Реєстрація
        Route::post('register', 'API\AuthController@register');

        # Авторизація
        Route::post('login', 'API\AuthController@login');

        # Припинення сеансу авторизованого користувача
        Route::post('logout', 'API\AuthController@logout')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Перевірка токена
        Route::get('check_token', 'API\AuthController@checkToken');
    }
);

// Операції по відновленню пароля
Route::group(
    [
        'prefix' => 'password',
    ],
    function () {
        # Відправити запит з емейлом для скидання паролю
        Route::post('email', 'API\PasswordController@sendResetLinkEmail')->name('password.email');

        # Зміна паролю
        Route::post('reset', 'API\PasswordController@reset');
    }
);

// Операції с профілем користувача
Route::group(
    [
        'prefix' => 'users',
    ],
    function () {
        # Отримання інформації про користувача (тільки публічні дані)
        Route::get('{user_id}/profile', 'API\ProfileController@getPublicData');

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
        Route::get('verification/{token}', 'API\ProfileController@verificationUserChanges')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Завантажити фотографію
        Route::get('download_image', 'API\ProfileController@downloadImage');
    }
);

// Відгуки
Route::group(
    [
        'prefix' => 'reviews',
    ],
    function () {
        # Додати відгук
        Route::post('add', 'API\ReviewController@addReview')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримати відгуки
        Route::get('show', 'API\ReviewController@showReviews');

        # Отримати рейтинг користувача
        Route::get('rating', 'API\ReviewController@getRating');
    }
);

// Чати
Route::group(
    [
        'prefix' => 'chats',
    ],
    function () {
        # Додати чат
        Route::post('add', 'API\ChatController@addChat')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримати чат
        Route::get('show', 'API\ChatController@showChats')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Видалити чат
        Route::post('delete', 'API\ChatController@deleteChat')->middleware(MIDDLEWARE_AUTH_BASIC);
    }
);

// Повідомлення
Route::group(
    [
        'prefix' => 'messages',
        'middleware' => MIDDLEWARE_AUTH_BASIC,
    ],
    function () {
        # Додати повiдомлення
        Route::post('add', 'API\MessagesController@addMessage');

        # Отримати повiдомлення
        Route::get('show', 'API\MessagesController@showMessages');

        # Підтвердження здійснення покупки (виконавець)
        Route::post('accept_shopping', 'API\MessagesController@acceptShoppingByPerformer');
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
        'prefix' => 'categories',
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

        # Вивід замовлень за фільтром
        Route::get('show', 'API\OrderController@showOrders')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід моїх замовлень
        Route::get('my/show', 'API\OrderController@showMyOrders')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід конкретного замовлення
        Route::get('{order_id}/show', 'API\OrderController@showOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Видалення замовлення
        Route::delete('{order_id}/delete', 'API\OrderController@deleteOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Підбір маршруту для замовлення
        Route::get('{order_id}/selection_route', 'API\OrderController@selectionRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Підтвердити виконання замовлення
        Route::post('confirm', 'API\OrderController@confirmOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Лічильник переглядів
        Route::post('{order_id}/add_look', 'API\OrderController@addLook');

        # Скарга на замовлення
        Route::post('{order_id}/strike', 'API\OrderController@strikeOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Список довідників для фільтру на сторінці Замовлення
        Route::get('filters', 'API\OrderController@getFilters');
    }
);

// Загрузка фото і створення мініатюр
Route::post('upload_image', 'API\ImageLoaderController@upload')->middleware(MIDDLEWARE_AUTH_BASIC);

// Маршрут
Route::group(
    [
        'prefix' => 'routes',
    ],
    function () {
        # Створення маршруту
        Route::post('add', 'API\RouteController@addRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід моїх маршрутів
        Route::get('my/show', 'API\RouteController@showMyRoutes')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Виведення маршруту за фільтром
        Route::get('show', 'API\RouteController@showRoutes')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід конкретного маршруту
        Route::get('{route_id}/show', 'API\RouteController@showRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Редагування маршруту
        Route::post('{route_id}/update', 'API\RouteController@updateRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Видалення маршруту
        Route::delete('{route_id}/delete', 'API\RouteController@deleteRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Підбір замовлення для маршруту
        Route::get('{route_id}/selection_order', 'API\RouteController@selectionOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Лічильник переглядів
        Route::post('{route_id}/add_look', 'API\RouteController@addLook');
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

// Ставки
Route::group(
    [
        'prefix' => 'rates',
    ],
    function () {
        # Зробити ставку
        Route::post('add', 'API\RateController@addRate')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Редагувати ставку
        Route::post('{rate_id}/update', 'API\RateController@updateRate')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Видалити ставку
        Route::delete('{rate_id}/delete', 'API\RateController@deleteRate')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Відхилити ставку
        Route::post('{rate_id}/reject', 'API\RateController@rejectRate')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Отримати ставки
        Route::get('show', 'API\RateController@showRates')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Переглянути ставку
        Route::get('{rate_id}/show', 'API\RateController@showRate')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід ставок для конкретного заказу
        Route::get('order/{order_id}/show', 'API\RateController@showRatesByOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід ставок для конкретного маршруту
        Route::get('route/{route_id}/show', 'API\RateController@showRatesByRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Прийняти ставку
        Route::post('{rate_id}/accept', 'API\RateController@acceptRate')->middleware(MIDDLEWARE_AUTH_BASIC);
    }
);

// Завдання
Route::group(
    [
        'prefix' => 'jobs',
        'middleware' => MIDDLEWARE_AUTH_BASIC,
    ],
    function () {
        # Створення завдання
        Route::post('add', 'API\JobController@addJob');

        # Підтвердження правильності покупки (замовник)
        Route::post('accept', 'API\JobController@acceptJob');

        # Оплата замовлення (формування параметрів для Liqpay оплати)
        Route::post('{rate_id}/liqpay_params', 'API\JobController@createLiqpayParams');
    }
);

# Оплата замовлення (отримання результату оплати від Liqpay)
Route::get('liqpay_result', 'API\JobController@resultLiqpay')->name('api.liqpay.result');

// Парсинг даних
Route::get('parser', 'API\ParserController');

// Завантаження файлу
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

// Заяви
Route::group(
    [
        'prefix' => 'statements',
        'middleware' => MIDDLEWARE_AUTH_BASIC,
    ],
    function () {
        # Створення заяви на пролонгацію замовлення
        Route::post('add', 'API\StatementController@addStatement');

        # Відхилити заяву на пролонгацію замовлення
        Route::post('{statement_id}/reject', 'API\StatementController@rejectStatement');

        # Підтвердити заяву на пролонгацію замовлення
        Route::post('{statement_id}/accept', 'API\StatementController@acceptStatement');
    }
);
