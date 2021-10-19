<?php

return [
    'login_successful'        => 'Вход выполнен успешно.',
    'logout_successful'       => 'Выход выполнен успешно.',
    'auth_failed'             => 'Неверное имя пользователя или пароль.',
    'register_successful'     => 'Регистрация прошла успешно.',
    'user_not_found'          => 'Пользователь не найден.',
    'old_password_incorrect'  => 'Текущий пароль неверен.',
    'token_incorrect'         => 'Токен неверен.',
    'updated_successful'      => 'Обновлено успешно.',
    'country_not_found'       => 'Страна не найдена.',
    'city_not_found'          => 'Город не найден.',
    'category_not_found'      => 'Категория не найдена.',
    'not_filled_profile'      => 'В профиле не указаны имя или фамилия или день рождения.',
    'order_not_found'         => 'Заказ не найден.',
    'route_not_found'         => 'Маршрут не найден.',
    'rate_not_found'          => 'Ставка не найдена.',
    'who_start_incorrect'     => 'Поле Пользователь не корректен.',
    'one_rate_per_order'      => 'Может быть только одна базовая ставка на заказ.',
    'three_rate_per_route'    => 'Может быть максимум три базовые ставки на маршрут.',
    'differs_from_basic_rate' => 'Параметр отличается от базовой ставки.',
    'not_owner_basic_rate'    => 'Вы не владелец базовой ставки.',
    'not_last_rate'           => 'Не последняя ставка.',
    'rate_not_accepted'       => 'Ставка не принята.',
    'rate_accepted'           => 'Ставка принята.',
    'unique_review'           => 'Вы уже оставляли отзыв об этой задаче',
    'review_not_allowed'      => 'Вы не можете оставить отзыв к этой задаче',
    'review_not_ready'        => 'Вы не можете оставить отзыв к незавершенной задаче',
    'type_freelancer'         => 'Фрилансер',
    'type_creator'            => 'Заказчик',
    'not_have_permission'     => 'У вас нет разрешения.',
    'already_have_complaint'  => 'От вас уже поступила жалоба.',
    'chat_not_found'          => 'Чат не найден.',
    'exists_active_statement' => 'Существует активное заявление.',
    'statement_max_limit'     => 'Было создано максимальное количество пролонгаций.',
    'deadline_not_arrived'    => 'Конечный срок не наступил.',
    'statement_not_found'     => 'Заявление не найдено.',
    'job_not_found'           => 'Задание не найдено.',
    'image_not_found'         => 'Рисунок не найден.',

    'send_token' => [
        'error' => 'Ошибка при отправке токена',
        'email' => 'Токен отправлен на адрес',
    ],

    # User's attribute
    'user' => [
        'genders' => [
            'male'    => 'Мужской',
            'female'  => 'Женский',
            'unknown' => 'Неизвестный',
        ],
        'validations' => [
            'valid'    => 'Проверенный',
            'no_valid' => 'Не проверенный',
        ],
        'statuses' => [
            'active'     => 'В работе',
            'not_active' => 'Временно не работаю',
            'banned'     => 'Забаненный',
            'removed'    => 'Удаленный',
        ],
    ],

    # Order's attribute
    'order' => [
        'statuses' => [
            'active'     => 'Активный',
            'closed'     => 'Закрытый',
            'ban'        => 'Забаненный',
            'successful' => 'Успешный',
        ],
    ],

    # Route's attribute
    'route' => [
        'statuses' => [
            'active'     => 'Активный',
            'closed'     => 'Закрытый',
            'ban'        => 'Забаненный',
            'successful' => 'Успешный',
        ],
        'transports' => [
            'car'   => 'Автомобиль',
            'bus'   => 'Автобус',
            'walk'  => 'Пешком',
            'train' => 'Поезд',
            'plane' => 'Самолет',
        ],
        'transports_prepositional' => [
            'car'   => 'на Автомобиле',
            'bus'   => 'на Автобусе',
            'walk'  => 'Пешком',
            'train' => 'Поездом',
            'plane' => 'Самолётом',
        ],
    ],
];
