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
    'country_or_city_start_search'  => 'Введите название города или страны. Минимум 2 буквы.',
    'country_or_city_not_found'     => 'Не найдены результаты. Проверьте правильность ввода.',
    'country_not_found'       => 'Страна не найдена.',
    'city_not_found'          => 'Город не найден.',
    'category_not_found'      => 'Категория не найдена.',
    'not_filled_profile'      => 'В профиле не указаны имя или фамилия или день рождения.',
    'order_not_found'         => 'Заказ не найден.',
    'order_exists'            => 'Заказ существует.',
    'route_not_found'         => 'Маршрут не найден.',
    'route_exists'            => 'Маршрут существует.',
    'dispute_exists'          => 'Спор существует.',
    'dispute_not_found'       => 'Спор не найден.',
    'rate_not_found'          => 'Ставка не найдена.',
    'rate_add_double'         => 'Дублирующая ставка.',
    'who_start_incorrect'     => 'Поле Пользователь не корректен.',
    'one_rate_per_order'      => 'Может быть только одна базовая ставка на заказ.',
    'three_rate_per_route'    => 'Может быть максимум три базовые ставки на маршрут.',
    'differs_from_basic_rate' => 'Параметр отличается от базовой ставки.',
    'not_owner_basic_rate'    => 'Вы не владелец базовой ставки.',
    'not_last_rate'           => 'Не последняя ставка.',
    'rate_not_accepted'       => 'Ставка не принята.',
    'rate_accepted'           => 'Ставка принята.',
    'rate_exists_limit_user_price' => 'Сумма вознаграждения не должна превышать сумму вознаграждения указанного на заказе.',
    'review_exists'           => 'Вы уже оставляли отзыв',
    'review_not_allowed'      => 'Вы не можете оставить отзыв на это задание',
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
    'lock_add_message'        => 'Запрещено добавлять сообщение.',
    'data_not_changed'        => 'Данные не изменились.',
    'update_denied'           => 'Обновление отклонено.',
    'file_not_exists'         => 'Файл не существует: ',
    'start_and_end_points_match' => 'Совпадают начальные и конечные пункты.',

    'wallet' => [
        'not_enough_funds' => 'Нет достаточно средств.',
        'exists_unfinished_withdrawals' => 'Есть незавершенная заявка на вывод денег.',
     ],

    'verification_code' => [
        'incorrect'         => 'Код подтверждения неверен',
        'send_error'        => 'Ошибка при отправке кода подтверждения',
        'send_by_email'     => 'Код подтверждения отправлен на адрес',
        'change_successful' => 'Данные успешно обновлены',
    ],

    # Сообщения при рассылках писем
    'email' => [
        'social_registration' => 'Регистрация через социальную сеть',
        'send_success'        => 'Сообщение отправлено, в ближайшее время с Вами свяжутся.',
        'send_error'          => 'Возникла ошибка при отправке сообщения.',
    ],

    # User attributes
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

    # Order attributes
    'order' => [
        'statuses' => [
            'active'     => 'Активная',
            'in_work'    => 'В работе',
            'closed'     => 'Закрытая',
            'failed'     => 'Неудачная',
            'successful' => 'Успешная',
            'banned'     => 'Запрещённая',
        ],
    ],

    # Route attributes
    'route' => [
        'statuses' => [
            'active'     => 'Активный',
            'in_work'    => 'В работе',
            'closed'     => 'Закрытый',
            'successful' => 'Успешный',
            'banned'     => 'Запрещённый',
        ],
    ],

    # Rate attributes
    'rate' => [
        'statuses' => [
            'active'     => 'Активная',
            'canceled'   => 'Отмененная',
            'rejected'   => 'Отклоненная',
            'accepted'   => 'Принятая',
            'buyed'      => 'Купленная',
            'successful' => 'Успешная',
            'done'       => 'Выполненная',
            'failed'     => 'Неудачная',
            'banned'     => 'Запрещённая',
        ],
    ],

    # Chat attributes
    'chat' => [
        'statuses' => [
            'active'   => 'Активный',
            'closed'   => 'Закрытый',
        ],
    ],

    # Dispute attributes
    'dispute' => [
        'statuses' => [
            'active'    => 'Активный',
            'appointed' => 'Назначен',
            'in_work'   => 'В работе',
            'closed'    => 'Закрытый',
            'canceled'  => 'Отмененный',
        ],
    ],

    # Payment attributes
    'payment' => [
        'statuses' => [
            'new'       => 'Новая',
            'appointed' => 'Назначена',
            'rejected'  => 'Отклоненная',
            'done'      => 'Выполненная',
        ],
        'types' => [
            'reward' => 'Вознаграждение',
            'refund' => 'Возврат средств',
        ],
    ],

    # Transaction attributes
    'transaction' => [
        'statuses' => [
            'new'       => 'Новая',
            'appointed' => 'Назначена',
            'rejected'  => 'Отклоненная',
            'done'      => 'Выполненная',
            'created'   => 'Созданная',
            'approved'  => 'Оплаченная',
            'canceled'  => 'Отмененная',
            'failed'    => 'Отказанная',
            'payed'     => 'Оплачено',
        ],
        'types' => [
            'payment' => 'Оплата заказа',
            'reward' => 'Вознаграждение',
            'refund' => 'Возврат средств',
        ],
    ],

    # Withdrawal attributes
    'withdrawal' => [
        'statuses' => [
            'new'         => 'Новая',
            'done'        => 'Выполненная',
            'in_progress' => 'В работе',
            'fail'        => 'Отказ',
            'expired'     => 'Устарела',
        ],
    ],
];
