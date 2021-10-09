<?php

return [
    'login_successful'        => 'Вхід успішний.',
    'logout_successful'       => 'Вихід успішний.',
    'auth_failed'             => 'Вказані облікові дані не збігаються з нашими записами.',
    'register_successful'     => 'Реєстрація успішна.',
    'user_not_found'          => 'Користувач не знайдений.',
    'old_password_incorrect'  => 'Поточний пароль неправильний.',
    'token_incorrect'         => 'Токен неправильний.',
    'updated_successful'      => 'Оновлено успішно.',
    'country_not_found'       => 'Країна не знайдена.',
    'city_not_found'          => 'Місто не знайдено',
    'category_not_found'      => 'Категорія не знайдена.',
    'not_filled_profile'      => 'У профілі не заповнено ім’я чи прізвище чи день народження.',
    'order_not_found'         => 'Замовлення не знайдено.',
    'route_not_found'         => 'Маршрут не знайдено.',
    'rate_not_found'          => 'Ставку не знайдено.',
    'who_start_incorrect'     => 'Поле Користувач помилковий.',
    'one_rate_per_order'      => 'Може бути лише одна основна ставка за замовлення.',
    'three_rate_per_route'    => 'Може бути не більше трьох базових ставок за маршрут.',
    'differs_from_basic_rate' => 'Параметр відрізняється від базової ставки.',
    'not_owner_basic_rate'    => 'Ви не є власником базової ставки.',
    'not_last_rate'           => 'Не остання ставка.',
    'rate_not_accepted'       => 'Ставка не прийнята.',
    'rate_accepted'           => 'Ставка прийнята.',
    'unique_review'           => 'Ви вже залишали відгук про це завдання',
    'review_not_allowed'      => 'Ви не можете залишити відгук на це завдання',
    'review_not_ready'        => 'Ви не можете залишити відгук на незакінчене завдання',
    'type_freelancer'         => 'Фрілансер',
    'type_creator'            => 'Замовник',
    'not_have_permission'     => 'У вас немає дозволу.',
    'already_have_complaint'  => 'Уже є скарга від вас.',
    'chat_not_found'          => 'Чат не знайдено.',
    'statement_max_limit'     => 'Було створено максимальну кількість пролонгацій.',
    'deadline_not_arrived'    => 'Кінцевий термін не надійшов.',
    'statement_not_found'     => 'Заяву не знайдено.',
    'job_not_found'           => 'Завдання не знайдено.',
    'image_not_found'         => 'Зображення не знайдено.',

    # User's attribute
    'user' => [
        # Gender
        'male'    => 'Чоловічий',
        'female'  => 'Жіночий',
        'unknown' => 'Невідомий',

        # User verification
        'valid'    => 'Перевірений',
        'no_valid' => 'Не перевірений',

        # Statuses
        'active'  => 'Активний',
        'banned'  => 'Заборонений',
        'removed' => 'Видалений',
    ],

    # Order's attribute
    'order' => [
        # Statuses
        'active'     => 'Активний',
        'closed'     => 'Закритий',
        'ban'        => 'Заборонений',
        'successful' => 'Успішний',
    ],
];
