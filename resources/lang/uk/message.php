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
    'country_or_city_start_search'  => 'Почніть вводити назву міста чи країни. Мінімум 2 літери.',
    'country_or_city_not_found'     => 'Не знайдено результатів. Перевірте правильність введення.',
    'country_not_found'       => 'Країна не знайдена.',
    'city_not_found'          => 'Місто не знайдено',
    'category_not_found'      => 'Категорія не знайдена.',
    'not_filled_profile'      => 'У профілі не заповнено ім’я чи прізвище чи день народження.',
    'order_not_found'         => 'Замовлення не знайдено.',
    'order_exists'            => 'Замовлення існує.',
    'route_not_found'         => 'Маршрут не знайдено.',
    'route_exists'            => 'Маршрут існує.',
    'dispute_exists'          => 'Суперечка існує.',
    'dispute_not_found'       => 'Суперечка не існує.',
    'rate_not_found'          => 'Ставку не знайдено.',
    'rate_add_double'         => 'Дублююча ставка.',
    'who_start_incorrect'     => 'Поле Користувач помилковий.',
    'one_rate_per_order'      => 'Може бути лише одна основна ставка за замовлення.',
    'three_rate_per_route'    => 'Може бути не більше трьох базових ставок за маршрут.',
    'differs_from_basic_rate' => 'Параметр відрізняється від базової ставки.',
    'not_owner_basic_rate'    => 'Ви не є власником базової ставки.',
    'not_last_rate'           => 'Не остання ставка.',
    'rate_not_accepted'       => 'Ставка не прийнята.',
    'rate_accepted'           => 'Ставка прийнята.',
    'rate_exists_limit_user_price' => 'Сума винагороди не повинна перевищувати суму винагороди, зазначеної на замовленні.',
    'unique_review'           => 'Ви вже залишали відгук про це завдання',
    'review_not_allowed'      => 'Ви не можете залишити відгук на це завдання',
    'review_not_ready'        => 'Ви не можете залишити відгук на незакінчене завдання',
    'type_freelancer'         => 'Фрілансер',
    'type_creator'            => 'Замовник',
    'not_have_permission'     => 'У вас немає дозволу.',
    'already_have_complaint'  => 'Уже є скарга від вас.',
    'chat_not_found'          => 'Чат не знайдено.',
    'exists_active_statement' => 'Існує активне замовлення.',
    'statement_max_limit'     => 'Було створено максимальну кількість пролонгацій.',
    'deadline_not_arrived'    => 'Кінцевий термін не надійшов.',
    'statement_not_found'     => 'Заяву не знайдено.',
    'job_not_found'           => 'Завдання не знайдено.',
    'image_not_found'         => 'Зображення не знайдено.',
    'lock_add_message'        => 'Заборонено добавляти повідомлення.',
    'data_not_changed'        => 'Дані не змінились.',
    'update_denied'           => 'Оновлення відхилено.',
    'file_not_exists'         => 'Файл не існує: ',

    'verification_code' => [
        'incorrect'         => 'Код підтвердження невірний',
        'send_error'        => 'Помилка при відправці коду підтвердження',
        'send_by_email'     => 'Код підтвердження відправлений на адресу',
        'change_successful' => 'Дані успішно оновлені',
    ],

    # Повідомлення при розсилках листів
    'email' => [
        'social_registration' => 'Реєстрація через соціальну мережу',
        'send_success'        => "Повідомлення надіслано, найближчим часом з Вами зв'яжуться.",
        'send_error'          => 'Виникла помилка при надсиланні повідомлення.',
    ],

    # User attributes
    'user' => [
        'genders' => [
            'male'    => 'Чоловічий',
            'female'  => 'Жіночий',
            'unknown' => 'Невідомий',
        ],
        'validations' => [
            'valid'    => 'Перевірений',
            'no_valid' => 'Не перевірений',
        ],
        'statuses' => [
            'active'     => 'Працюю',
            'not_active' => 'Тимчасово не працюю',
            'banned'     => 'Заборонений',
            'removed'    => 'Видалений',
        ],
    ],

    # Order attributes
    'order' => [
        'statuses' => [
            'active'     => 'Активна',
            'in_work'    => 'В роботі',
            'closed'     => 'Закрита',
            'failed'     => 'Виконана',
            'successful' => 'Успішна',
            'banned'     => 'Заборонений',
        ],
    ],

    # Route attributes
    'route' => [
        'statuses' => [
            'active'     => 'Активний',
            'in_work'    => 'В роботі',
            'closed'     => 'Закритий',
            'successful' => 'Успішний',
            'banned'     => 'Заборонений',
        ],
    ],

    # Rate attributes
    'rate' => [
        'statuses' => [
            'active'     => 'Активна',
            'canceled'   => 'Відмінена',
            'rejected'   => 'Відхилена',
            'accepted'   => 'Прийнята',
            'buyed'      => 'Куплена',
            'successful' => 'Успішна',
            'done'       => 'Виконана',
            'failed'     => 'Виконана',
            'banned'     => 'Заборонена',
        ],
    ],

    # Chat attributes
    'chat' => [
        'statuses' => [
            'active'   => 'Активний',
            'closed'   => 'Закритий',
        ],
    ],

    # Dispute attributes
    'dispute' => [
        'statuses' => [
            'active'    => 'Активний',
            'appointed' => 'Призначений',
            'in_work'   => 'В роботі',
            'closed'    => 'Закритий',
            'canceled'  => 'Відмінений',
        ],
    ],

    # Payment attributes
    'payment' => [
        'statuses' => [
            'active'    => 'Активна',
            'appointed' => 'Призначена',
            'rejected'  => 'Відхилена',
            'done'      => 'Виконана',
        ],
        'types' => [
            'reward' => 'Винагорода',
            'refund' => 'Повернення коштів',
        ],
    ],
];
