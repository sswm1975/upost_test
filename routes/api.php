<?php

use Illuminate\Support\Facades\Route;

defined('MIDDLEWARE_AUTH_BASIC') or define('MIDDLEWARE_AUTH_BASIC', 'auth:api');

Route::namespace('API')->group(function ($route) {
    # Аутентифікація
    $route->prefix('auth')->group(function ($route) {
        # Аутентифікація
        $route->post('login', 'AuthController@login')->middleware('api_throttle:5,10'); # 5 спроб, 10 хвилин простою

        # Аутентифікація за допомогою соц.мережі (Google, Facebook)
        $route->post('social', 'AuthController@social')->middleware('api_throttle:5,10'); # 5 спроб, 10 хвилин простою

        # Припинення сеансу авторизованого користувача
        $route->post('logout', 'AuthController@logout')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Реєстрація
        $route->post('register', 'AuthController@register')->middleware('api_throttle:5,10'); # 5 спроб, 10 хвилин

        # Перевірка токена
        $route->get('check_token', 'AuthController@checkToken');

        # Дані авторизованого користувача
        $route->get('user', 'AuthController@getAuthUser');
    });

    # Операції по відновленню пароля
    $route->prefix('password')->group(function ($route) {
        # Відправити запит з емейлом для скидання паролю
        $route->post('email', 'PasswordController@sendResetLinkEmail')->name('password.email');

        # Зміна паролю
        $route->post('reset', 'PasswordController@reset');
    });

    # Операції с профілем користувача
    $route->prefix('users')->group(function ($route) {
        # Отримання інформації про користувача (тільки публічні дані)
        $route->get('{user_id}/profile', 'ProfileController@getPublicData');

        # Отримання інформації про користувача (всі дані)
        $route->get('profile', 'ProfileController@getPrivateData')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна даних профілю (тільки публічні дані)
        $route->post('profile/update', 'ProfileController@updatePublicData')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна даних мов та валют
        $route->post('language/update', 'ProfileController@updateLanguage')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна паролю
        $route->post('password/update', 'ProfileController@updatePassword')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна логіну: телефону та/або емейлу
        $route->post('login/update', 'ProfileController@updateLogin')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Зміна даних пластикової картки
        $route->post('card/update', 'ProfileController@updateCard')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Верифікація зміни даних користувача (тільки пароль/логін/картка)
        $route->get('verification/{token}', 'ProfileController@verificationUserChanges')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Перевірка заповнення профілю (ім'я, прізвище, дата народження) у авторизованого користувача.
        $route->get('profile/is_filled', 'ProfileController@isProfileFilled')->middleware(MIDDLEWARE_AUTH_BASIC);
    });

    # Замовлення
    $route->prefix('orders')->group(function ($route) {
        # Створення замовлення
        $route->post('add', 'OrderController@addOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Редагування замовлення
        $route->post('{order_id}/update', 'OrderController@updateOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід замовлень за фільтром
        $route->get('show', 'OrderController@showOrders');

        # Вивід моїх замовлень
        $route->get('my/show', 'OrderController@showMyOrders')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід конкретного замовлення
        $route->get('{order_id}/show', 'OrderController@showOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід конкретного замовлення для редагування
        $route->get('{order_id}/edit', 'OrderController@showOrderForEdit')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Для замовлення підібрать маршрути
        $route->get('{order_id}/selection_routes', 'OrderController@selectionRoutes');

        # Закриття замовлення
        $route->post('{order_id}/close', 'OrderController@closeOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Закриття декілька замовлень
        $route->post('close', 'OrderController@closeOrders')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Лічильник переглядів
        $route->post('{order_id}/add_look', 'OrderController@addLook');

        # Скарга на замовлення
        $route->post('{order_id}/strike', 'OrderController@strikeOrder')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Заказы по выбранному маршруту
        $route->get('route/{route_id}/show', 'OrderController@showOrdersByRoute')->middleware(MIDDLEWARE_AUTH_BASIC);
    });

    # Маршрути
    $route->prefix('routes')->group(function ($route) {
        # Створення маршруту
        $route->post('add', 'RouteController@addRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Вивід моїх маршрутів
        $route->get('my/show', 'RouteController@showMyRoutes')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Виведення маршруту за фільтром
        $route->get('show', 'RouteController@showRoutes');

        # Вивід конкретного маршруту
        $route->get('{route_id}/show', 'RouteController@showRoute');

        # Редагування маршруту
        $route->post('{route_id}/update', 'RouteController@updateRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Закриття маршруту
        $route->post('{route_id}/close', 'RouteController@closeRoute')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Закриття декілька маршрутів
        $route->post('close', 'RouteController@closeRoutes')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Підбір замовлень для маршруту
        $route->get('{route_id}/selection_orders', 'RouteController@selectionOrders')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Лічильник переглядів
        $route->post('{route_id}/add_look', 'RouteController@addLook');
    });

    # Ставки
    $route->prefix('rates')->middleware(MIDDLEWARE_AUTH_BASIC)->group(function ($route) {
        # Создать ставку
        $route->post('add', 'RateController@addRate');

        # Изменить ставку
        $route->post('{rate_id}/update', 'RateController@updateRate');

        # Просмотр ставки
        $route->get('{rate_id}/show', 'RateController@showRate');

        # Отменить ставку
        $route->post('{rate_id}/cancel', 'RateController@cancelRate');

        # Удалить ставку
        $route->delete('{rate_id}/delete', 'RateController@deleteRate');

        # Оклонить ставку
        $route->post('{rate_id}/reject', 'RateController@rejectRate');

        # Подготовить данные для оплаты по выбранной ставке (формирование параметров для Liqpay-платежа).
        $route->post('{rate_id}/prepare_payment', 'RateController@preparePayment');

        # Подтверждение ставки - Оплата заказа (от Liqpay пришёл результат оплаты)
        $route->post('callback_payment', 'RateController@callbackPayment')->withoutMiddleware(MIDDLEWARE_AUTH_BASIC);

        # Подтверждение покупки товара исполнителем (Путешественник по ставке купил товар)
        $route->post('{rate_id}/buyed', 'RateController@buyedRate');

        # Получение товара заказчиком
        $route->post('{rate_id}/successful', 'RateController@successfulRate');

        # Получить ставки по выбранному заказу
        $route->get('order/{id}/show', 'RateController@showRatesByOrder');

        # Ставки, которые не смотрел владелец заказа
        $route->get('not_viewed_by_customer', 'RateController@getRatesNotViewedByCustomer');

        # Установка признака "Ставка просмотрена заказчиком".
        $route->post('set_viewed_by_customer', 'RateController@setViewedByCustomer');
    });

    # Отзывы
    $route->prefix('reviews')->group(function ($route) {
        # Добавить отзыв
        $route->post('add', 'ReviewController@addReview')->middleware(MIDDLEWARE_AUTH_BASIC);

        # Получить отзывы
        $route->get('show', 'ReviewController@showReviews');

        # Получить рейтинги на основании перерасчета
        $route->get('{recipient_id}/calc', 'ReviewController@getCalcRating');
    });

    # Чаты
    $route->prefix('chats')->middleware(MIDDLEWARE_AUTH_BASIC)->group(function ($route) {
        # Получить список чатов
        $route->get('show', 'ChatController@showChats');

        # Получить список сообщений по коду чата
        $route->get('{chat_id}/messages/show', 'ChatController@showMessages');

        # Получить количество непрочитанных сообщений
        $route->get('get_count_unread_messages', 'ChatController@getCountUnreadMessages');
    });

    # Сообщения
    $route->prefix('messages')->middleware(MIDDLEWARE_AUTH_BASIC)->group(function ($route) {
        # Добавить сообщение
        $route->post('add', 'MessagesController@addMessage');

        # Получить список сообщений по кодам маршрута и заказа
        $route->get('show', 'MessagesController@showMessages');
    });

    # Споры
    $route->prefix('disputes')->middleware(MIDDLEWARE_AUTH_BASIC)->group(function ($route) {
        # Создать спор
        $route->post('add', 'DisputeController@addDispute');

        # Получить спор по коду
        $route->get('{id}/show', 'DisputeController@showDisputeById');

        # Получить список споров по фильтру
        $route->get('show', 'DisputeController@showDispute');

        # Отменить спор
        $route->post('{id}/cancel', 'DisputeController@cancelDispute');

        # Получить справочник "Проблемы спора"
        $route->get('problems/{id?}', 'DisputeController@getProblems');

        # Получить количество споров по фильтру (используется админкой)
        $route->get('counter', 'DisputeController@getDisputesCounter')->name('api.disputes.counter')->withoutMiddleware(MIDDLEWARE_AUTH_BASIC);
    });

    # Кошелек
    $route->prefix('purses')->middleware(MIDDLEWARE_AUTH_BASIC)->group(function ($route) {
        $route->get('show', 'PurseController@show');
    });

    # Справочники
    $route->prefix('handbooks')->group(function ($route) {
        # Список довідників для фільтру на сторінці Замовлення/Маршрути
        $route->get('/', 'HandbooksController@getHandbooks');

        # Валюты
        $route->get('currencies', 'HandbooksController@getCurrencies');

        # Курсы валют
        $route->get('currency_rates', 'HandbooksController@getCurrencyRates');
    });
});

// Відправка лістів
Route::group(
    [
        'prefix' => 'mail',
    ],
    function () {
        # Відправити листа з форми "Є запитання?"
        Route::post('have_question', 'API\MailController@haveQuestion');
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

        # Інкрементний пошук Країни або Міста
        Route::get('search', 'API\CountryController@searchCountryOrCity');
    }
);

// Загрузка фото і створення мініатюр
Route::post('upload_image', 'API\ImageLoaderController@upload')->middleware(MIDDLEWARE_AUTH_BASIC);

# Удалить изображение
Route::delete('image', 'API\ImageLoaderController@deleteImage')->middleware(MIDDLEWARE_AUTH_BASIC);

// Парсинг даних
Route::get('parser', 'API\ParserController')->middleware(MIDDLEWARE_AUTH_BASIC);

// Завантаження файлу
Route::post('upload', 'API\UploadController@upload')->middleware(MIDDLEWARE_AUTH_BASIC);

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
