<?php

/*
|--------------------------------------------------------------------------
| Validation Language Lines
|--------------------------------------------------------------------------
|
| The following language lines contain the default error messages used by
| the validator class. Some of these rules have multiple versions such
| as the size rules. Feel free to tweak each of these messages here.
|
*/

return [
    'accepted'             => 'Ви повинні прийняти :attribute.',
    'active_url'           => 'Поле :attribute не є правильним URL.',
    'after'                => 'Поле :attribute має містити дату не раніше :date.',
    'after_or_equal'       => 'Поле :attribute має містити дату не раніше, або дорівнюватися :date.',
    'alpha'                => 'Поле :attribute має містити лише літери.',
    'alpha_dash'           => 'Поле :attribute має містити лише літери, цифри, тире та підкреслення.',
    'alpha_num'            => 'Поле :attribute має містити лише літери та цифри.',
    'array'                => 'Поле :attribute має бути масивом.',
    'attached'             => 'Цей :attribute вже прикріплений.',
    'before'               => 'Поле :attribute має містити дату не пізніше :date.',
    'before_or_equal'      => 'Поле :attribute має містити дату не пізніше, або дорівнюватися :date.',
    'between'              => [
        'array'   => 'Поле :attribute має містити від :min до :max елементів.',
        'file'    => 'Розмір файлу у полі :attribute має бути не менше :min та не більше :max кілобайт.',
        'numeric' => 'Поле :attribute має бути між :min та :max.',
        'string'  => 'Текст у полі :attribute має бути не менше :min та не більше :max символів.',
    ],
    'boolean'              => 'Поле :attribute повинне містити логічний тип.',
    'confirmed'            => 'Поле :attribute не збігається з підтвердженням.',
    'date'                 => 'Поле :attribute не є датою.',
    'date_equals'          => 'Поле :attribute має бути датою рівною :date.',
    'date_format'          => 'Поле :attribute не відповідає формату :format.',
    'different'            => 'Поля :attribute та :other повинні бути різними.',
    'digits'               => 'Довжина цифрового поля :attribute повинна дорівнювати :digits.',
    'digits_between'       => 'Довжина цифрового поля :attribute повинна бути від :min до :max.',
    'dimensions'           => 'Поле :attribute містить неприпустимі розміри зображення.',
    'distinct'             => 'Поле :attribute містить значення, яке дублюється.',
    'email'                => 'Поле :attribute повинне містити коректну електронну адресу.',
    'ends_with'            => 'Поле :attribute має закінчуватися одним з наступних значень: :values',
    'exists'               => 'Вибране для :attribute значення не коректне.',
    'file'                 => 'Поле :attribute має містити файл.',
    'filled'               => 'Поле :attribute є обов\'язковим для заповнення.',
    'gt'                   => [
        'array'   => 'Поле :attribute має містити більше ніж :value елементів.',
        'file'    => 'Поле :attribute має бути більше ніж :value кілобайт.',
        'numeric' => 'Поле :attribute має бути більше ніж :value.',
        'string'  => 'Поле :attribute має бути більше ніж :value символів.',
    ],
    'gte'                  => [
        'array'   => 'Поле :attribute має містити :value чи більше елементів.',
        'file'    => 'Поле :attribute має дорівнювати чи бути більше ніж :value кілобайт.',
        'numeric' => 'Поле :attribute має дорівнювати чи бути більше ніж :value.',
        'string'  => 'Поле :attribute має дорівнювати чи бути більше ніж :value символів.',
    ],
    'image'                => 'Поле :attribute має містити зображення.',
    'in'                   => 'Вибране для :attribute значення не коректне.',
    'in_array'             => 'Значення поля :attribute не міститься в :other.',
    'integer'              => 'Поле :attribute має містити ціле число.',
    'ip'                   => 'Поле :attribute має містити IP адресу.',
    'ipv4'                 => 'Поле :attribute має містити IPv4 адресу.',
    'ipv6'                 => 'Поле :attribute має містити IPv6 адресу.',
    'json'                 => 'Дані поля :attribute мають бути у форматі JSON.',
    'lt'                   => [
        'array'   => 'Поле :attribute має містити менше ніж :value items.',
        'file'    => 'Поле :attribute має бути менше ніж :value кілобайт.',
        'numeric' => 'Поле :attribute має бути менше ніж :value.',
        'string'  => 'Поле :attribute має бути менше ніж :value символів.',
    ],
    'lte'                  => [
        'array'   => 'Поле :attribute має містити не більше ніж :value елементів.',
        'file'    => 'Поле :attribute має дорівнювати чи бути менше ніж :value кілобайт.',
        'numeric' => 'Поле :attribute має дорівнювати чи бути менше ніж :value.',
        'string'  => 'Поле :attribute має дорівнювати чи бути менше ніж :value символів.',
    ],
    'max'                  => [
        'array'   => 'Поле :attribute повинне містити не більше :max елементів.',
        'file'    => 'Файл в полі :attribute має бути не більше :max кілобайт.',
        'numeric' => 'Поле :attribute має бути не більше :max.',
        'string'  => 'Текст в полі :attribute повинен мати довжину не більшу за :max.',
    ],
    'mimes'                => 'Поле :attribute повинне містити файл одного з типів: :values.',
    'mimetypes'            => 'Поле :attribute повинне містити файл одного з типів: :values.',
    'min'                  => [
        'array'   => 'Поле :attribute повинне містити не менше :min елементів.',
        'file'    => 'Розмір файлу у полі :attribute має бути не меншим :min кілобайт.',
        'numeric' => 'Поле :attribute повинне бути не менше :min.',
        'string'  => 'Текст у полі :attribute повинен містити не менше :min символів.',
    ],
    'multiple_of'          => 'Поле :attribute повинно містити декілька :value',
    'not_in'               => 'Вибране для :attribute значення не коректне.',
    'not_regex'            => 'Формат поля :attribute не вірний.',
    'numeric'              => 'Поле :attribute повинно містити число.',
    'password'             => 'Хибний пароль.',
    'present'              => 'Поле :attribute повинне бути присутнє.',
    'prohibited'           => 'Поле :attribute заборонено.',
    'prohibited_if'        => 'Поле :attribute заборонено, коли :other дорівнює :value.',
    'prohibited_unless'    => 'Поле :attribute заборонено, якщо тільки :other не знаходиться в :values.',
    'regex'                => 'Поле :attribute має хибний формат.',
    'relatable'            => 'Цей :attribute може бути не пов\'язаний з цим ресурсом.',
    'required'             => 'Поле :attribute є обов\'язковим для заповнення.',
    'required_if'          => 'Поле :attribute є обов\'язковим для заповнення, коли :other є рівним :value.',
    'required_unless'      => 'Поле :attribute є обов\'язковим для заповнення, коли :other відрізняється від :values',
    'required_with'        => 'Поле :attribute є обов\'язковим для заповнення, коли :values вказано.',
    'required_with_all'    => 'Поле :attribute є обов\'язковим для заповнення, коли :values вказано.',
    'required_without'     => 'Поле :attribute є обов\'язковим для заповнення, коли :values не вказано.',
    'required_without_all' => 'Поле :attribute є обов\'язковим для заповнення, коли :values не вказано.',
    'same'                 => 'Поля :attribute та :other мають збігатися.',
    'size'                 => [
        'array'   => 'Поле :attribute повинне містити :size елементів.',
        'file'    => 'Файл у полі :attribute має бути розміром :size кілобайт.',
        'numeric' => 'Поле :attribute має бути довжини :size.',
        'string'  => 'Текст у полі :attribute повинен містити :size символів.',
    ],
    'starts_with'          => 'Поле :attribute повинне починатися з одного з наступних значень: :values',
    'string'               => 'Поле :attribute повинне містити текст.',
    'timezone'             => 'Поле :attribute повинне містити коректну часову зону.',
    'unique'               => 'Вказане значення поля :attribute вже існує.',
    'uploaded'             => 'Завантаження :attribute не вдалося.',
    'url'                  => 'Формат поля :attribute хибний.',
    'uuid'                 => 'Поле :attribute має бути коректним UUID ідентифікатором.',

    'custom' => [
        'photo' => [
            'base64_image' => 'Хибний формат зображення.',
        ],
        'user_phone' => [
            'phone' => 'Телефон хибний.',
        ],
        'user_card_number' => [
            'bankcard' => 'Номер картки хибний.',
        ],
        'rate_id' => [
            'owner_rate' => 'У вас немає дозволу.',
        ],
        'user_resume' => [
            'censor' => 'У резюме є непристойні слова.',
            'not_phone' => 'Заборонено залишати контактні дані в резюме.',
        ],
        'order_text' => [
            'censor' => 'В замовленні є непристойні слова.',
            'not_phone' => 'Заборонено залишати контактні дані в замовленні.',
        ],
    ],

    'attributes'           => [
        'address'               => 'Адреса',
        'age'                   => 'Вік',
        'available'             => 'Доступно',
        'city'                  => 'Місто',
        'content'               => 'Контент',
        'country'               => 'Країна',
        'date'                  => 'Дата',
        'day'                   => 'День',
        'description'           => 'Опис',
        'email'                 => 'E-mail адреса',
        'excerpt'               => 'Уривок',
        'first_name'            => 'Ім\'я',
        'gender'                => 'Стать',
        'hour'                  => 'Година',
        'last_name'             => 'Прізвище',
        'minute'                => 'Хвилина',
        'mobile'                => 'Моб. номер',
        'month'                 => 'Місяць',
        'name'                  => 'Ім\'я',
        'password'              => 'Пароль',
        'password_confirmation' => 'Підтвердження пароля',
        'phone'                 => 'Телефон',
        'second'                => 'Секунда',
        'sex'                   => 'Стать',
        'size'                  => 'Розмір',
        'time'                  => 'Час',
        'title'                 => 'Назва',
        'username'              => 'Нікнейм',
        'year'                  => 'Рік',
	    'login'                 => 'Логін',

        'addressee' => 'Адреса',
        'chat_id' => 'Код чату',
        'city_from' => 'Місто (звідки)',
        'city_to' => 'Місто (куди)',
        'comment' => 'Коментар',
        'count' => 'Кількість',
        'country_from' => 'Країна (звідки)',
        'country_to' => 'Країна (куди)',
        'date_from' => 'Початкова дата',
        'date_to' => 'Кінцева дата',
        'files' => 'Файли',
        'from_user' => 'Користувач',
        'id' => 'Код',
        'job_id' => 'Код завдання',
        'lang' => 'Мова',
        'message_attach' => 'Вкладення',
        'message_text' => 'Текст',
        'old_password' => 'Старий пароль',
        'order_category' => 'Категорія',
        'order_count' => 'Кількість',
        'order_currency' => 'Валюта',
        'order_from_country' => 'Місто (звідки)',
        'order_id' => 'Код заказу',
        'order_name' => 'Назва заказу',
        'order_price' => 'Ціна заказу',
        'order_price_usd' => 'Ціна заказу (в доларах)',
        'order_product_link' => 'Посилання на товар',
        'order_size' => 'Розмір',
        'order_text' => 'Опис',
        'order_weight' => 'Вага',
        'page' => 'Сторінка',
        'pages' => 'Сторінки',
        'parent_id' => 'Код власника',
        'photo' => 'Зображення',
        'photo_type' => 'Тип',
        'photos' => 'Зображення',
        'problem_id' => 'Код помилки',
        'rate_currency' => 'Валюта',
        'rate_deadline' => 'Дата закінчення',
        'rate_id' => 'Код',
        'rate_price' => 'Ціна',
        'rate_text' => 'Текст',
        'rate_type' => 'Тип',
        'rating' => 'Рейтинг',
        'remove_photo' => 'Позначка знищення фото',
        'route_end' => 'Дата закінчення',
        'route_from_city' => 'Місто (звідки)',
        'route_from_country' => 'Країна (звідки)',
        'route_id' => 'Код',
        'route_start' => 'Дата початку',
        'route_to_city' => 'Місто (куди)',
        'route_to_country' => 'Країна (куди)',
        'route_transport' => 'Тип транспорту',
        'show' => 'Показ',
        'sort' => 'Сортування',
        'status' => 'Статус',
        'strike_id' => 'Код',
        'to_user' => 'Користувач',
        'type' => 'Тип',
        'url' => 'Посилання',
        'user_birthday' => 'Дата народження',
        'user_card_name' => 'Назва на платіжній картці',
        'user_card_number' => 'Номер платіжній карти',
        'user_city' => 'Місто',
        'user_currency' => 'Валюта',
        'user_email' => 'E-Mail адреса',
        'user_gender' => 'Стать',
        'user_id' => 'Код',
        'user_lang' => 'Мова',
        'user_name' => 'Им\'я',
        'user_password' => 'Пароль',
        'user_password_confirmation' => 'Підтвердження пароля',
        'user_phone' => 'Телефон',
        'user_photo' => 'Фото',
        'user_resume' => 'Резюме/Біографія',
        'user_status' => 'Статус',
        'user_surname' => 'Прізвище',
        'who_start' => 'Користувач',
        'token' => 'Токен',
    ],
];
