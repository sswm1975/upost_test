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
        'image' => [
            'base64_image' => 'Хибний формат зображення.',
        ],
        'phone' => [
            'phone' => 'Телефон хибний.',
        ],
        'card_number' => [
            'bankcard' => 'Номер картки хибний.',
        ],
        'rate_id' => [
            'owner_rate' => 'У вас немає дозволу.',
        ],
        'resume' => [
            'censor' => 'У резюме є непристойні слова.',
            'not_phone' => 'Заборонено залишати контактні дані в резюме.',
        ],
        'text' => [
            'censor' => 'В тексті є непристойні слова.',
            'not_phone' => 'Заборонено залишати контактні дані в замовленні.',
        ],
    ],

    'attributes'           => [
        'address' => 'Адреса',
        'addressee' => 'Адреса',
        'age' => 'Вік',
        'available' => 'Доступно',
        'birthday' => 'Дата народження',
        'card_name' => 'Назва на платіжній картці',
        'card_number' => 'Номер платіжній карти',
        'category' => 'Категорія',
        'chat_id' => 'Код чату',
        'city' => 'Місто',
        'city_id' => 'Місто',
        'city_from' => 'Місто (звідки)',
        'city_to' => 'Місто (куди)',
        'comment' => 'Коментар',
        'content' => 'Контент',
        'count' => 'Кількість',
        'country' => 'Країна',
        'country_from' => 'Країна (звідки)',
        'country_to' => 'Країна (куди)',
        'currency' => 'Валюта',
        'current_password' => 'Поточний пароль',
        'date' => 'Дата',
        'date_from' => 'Початкова дата',
        'date_to' => 'Кінцева дата',
        'day' => 'День',
        'description' => 'Опис',
        'email' => 'E-mail адреса',
        'deadline' => 'Дата закінчення',
        'excerpt' => 'Уривок',
        'files' => 'Файли',
        'first_name' => 'Ім\'я',
        'from_city' => 'Місто (звідки)',
        'from_city_id' => 'Місто (звідки)',
        'from_country' => 'Країна (звідки)',
        'from_country_id' => 'Країна (звідки)',
        'from_user' => 'Користувач',
        'gender' => 'Стать',
        'hour' => 'Година',
        'id' => 'Код',
        'job_id' => 'Код завдання',
        'lang' => 'Мова',
        'last_name' => 'Прізвище',
        'login' => 'Логін',
        'message_attach' => 'Вкладення',
        'message_text' => 'Текст',
        'minute' => 'Хвилина',
        'mobile' => 'Моб. номер',
        'month' => 'Місяць',
        'name' => 'Ім\'я',
        'old_password' => 'Старий пароль',
        'page' => 'Сторінка',
        'pages' => 'Сторінки',
        'parent_id' => 'Код власника',
        'password' => 'Пароль',
        'password_confirmation' => 'Підтвердження пароля',
        'phone' => 'Телефон',
        'photo' => 'Зображення',
        'photo_type' => 'Тип',
        'photos' => 'Зображення',
        'price' => 'Ціна заказу',
        'price_usd' => 'Ціна заказу (в доларах)',
        'problem_id' => 'Код помилки',
        'product_link' => 'Посилання на товар',
        'rate_currency' => 'Валюта',
        'rate_deadline' => 'Дата закінчення',
        'rate_id' => 'Код ставки',
        'rate_price' => 'Ціна ставки',
        'rate_text' => 'Текст ставки',
        'rate_type' => 'Тип ставки',
        'rating' => 'Рейтинг',
        'remove_photo' => 'Позначка знищення фото',
        'resume' => 'Резюме/Біографія',
        'review_type' => 'Тип відгука',
        'second' => 'Секунда',
        'sex' => 'Стать',
        'show' => 'Показ',
        'size' => 'Розмір',
        'sort' => 'Сортування',
        'start' => 'Дата початку',
        'status' => 'Статус',
        'strike_id' => 'Код скарги',
        'surname' => 'Прізвище',
        'text' => 'Опис',
        'time' => 'Час',
        'title' => 'Назва',
        'to_city' => 'Місто (куди)',
        'to_city_id' => 'Місто (куди)',
        'to_country' => 'Країна (куди)',
        'to_country_id' => 'Країна (куди)',
        'to_user' => 'Користувач',
        'token' => 'Токен',
        'transport' => 'Тип транспорту',
        'type' => 'Тип',
        'url' => 'Посилання',
        'username' => 'Нікнейм',
        'weight' => 'Вага',
        'who_start' => 'Користувач',
        'year' => 'Рік',
        'question' => 'Запитання',
        'check' => 'Умови згоди',
    ],
];
