<?php

return [
    'performer_suggested_route' => [
        'en' => 'The traveler suggested a route.',
        'ru' => 'Путешественник предложил маршрут.',
        'uk' => 'Мандрівник запропонував маршрут.',
    ],
    'performer_updated_route' => [
        'en' => 'The traveler updated a route.',
        'ru' => 'Путешественник обновил маршрут.',
        'uk' => 'Мандрівник обновив маршрут.',
    ],
    'performer_canceled_route' => [
        'en' => 'The traveler canceled a route.',
        'ru' => 'Путешественник отменил маршрут.',
        'uk' => 'Мандрівник скасував маршрут.',
    ],
    'performer_deleted_route' => [
        'en' => 'The traveler deleted a route.',
        'ru' => 'Путешественник удалил маршрут.',
        'uk' => 'Мандрівник видалив маршрут.',
    ],
    'customer_rejected_route' => [
        'en' => 'The customer rejected a route.',
        'ru' => 'Заказчик отклонил маршрут.',
        'uk' => 'Замовник відхилив маршрут.',
    ],
    'customer_paid_order' => [
        'en' => 'The customer paid for the order.',
        'ru' => 'Заказчик оплатил заказ.',
        'uk' => 'Замовник оплатив замовлення.',
    ],
    'performer_buyed_product' => [
        'en' => 'The traveler buyed product.',
        'ru' => 'Путешественник купил товар.',
        'uk' => 'Мандрівник купив товар.',
    ],
    'customer_received_order' => [
        'en' => 'The customer has received the product.',
        'ru' => 'Заказчик получил товар.',
        'uk' => 'Замовник отримав товар.',
    ],
    'dispute_opened' => [
        'en' => '<span>dispute_initiator</span> opened a dispute because:<br><span>dispute_problem</span><br><span>dispute_text</span>',
        'ru' => '<span>dispute_initiator</span> открыл спор по причине:<br><span>dispute_problem</span><br><span>dispute_text</span>',
        'uk' => '<span>dispute_initiator</span> відкрив суперечку з причин:<br><span>dispute_problem</span><br><span>dispute_text</span>',
    ],
    'dispute_canceled' => [
        'en' => 'The dispute has been canceled.',
        'ru' => 'Спор отменён.',
        'uk' => 'Суперечка скасована.',
    ],
    'dispute_close_info_option1' => [
        'en' => 'What to do next, read the instructions at the link https://post.tantal-web.top/faq/info1.html',
        'ru' => 'Что делать дальше ознакомьтесь в инструкции по ссылке https://post.tantal-web.top/faq/info1.html',
        'uk' => 'Що робити далі ознайомтесь в інструкції з посилання на https://post.tantal-web.top/faq/info1.html',
    ],
    'dispute_close_info_option2' => [
        'en' => 'What to do next, read the instructions at the link https://post.tantal-web.top/faq/info2.html',
        'ru' => 'Что делать дальше ознакомьтесь в инструкции по ссылке https://post.tantal-web.top/faq/info2.html',
        'uk' => 'Що робити далі ознайомтесь в інструкції з посилання на https://post.tantal-web.top/faq/info2.html',
    ],
    'dispute_in_work' => [
        'en' => 'Hello, my name is <span>manager_name</span>, and I will be your moderator in this debate! Wait for further instructions from me!',
        'ru' => 'Здравствуйте, меня зовут <span>manager_name</span>, и я буду вашим модератором в данном диспуте! Дождитесь дальнейших указаний с моей стороны!',
        'uk' => 'Вітаю, мене звуть <span>manager_name</span>, і я буду вашим модератором у цьому диспуті! Дочекайтеся подальших вказівок з мого боку!',
    ],

    /* Причины закрытия спора */
    'traveler_not_deliver_product' => [
        'en' => 'The traveler did not deliver the goods before the deadline.',
        'ru' => 'Путешественник не доставил товар до дедлайна.',
        'uk' => 'Мандрівник не доставив товар до дедлайну.',
    ],
    'traveler_not_respond' => [
        'en' => 'The traveler does not respond to messages for more than 3 days.',
        'ru' => 'Путешественник не отвечает на сообщения более 3 суток.',
        'uk' => 'Мандрівник не відповідає на повідомлення понад 3 доби.',
    ],
    'traveler_provided_defective_product' => [
        'en' => 'The traveler provided a defective product.',
        'ru' => 'Путешественник предоставил бракованный товар.',
        'uk' => 'Мандрівник надав бракований товар.',
    ],
    'customer_not_come_product' => [
        'en' => 'The customer did not come for the goods.',
        'ru' => 'Заказчик не пришел за товаром.',
        'uk' => 'Замовник не прийшов по товар.',
    ],
    'customer_not_respond' => [
        'en' => 'The customer does not respond to messages for more than a day.',
        'ru' => 'Заказчик не отвечает на сообщения более суток.',
        'uk' => 'Замовник не відповідає на повідомлення понад добу.',
    ],
    /* END Причины закрытия спора */

    /* Виды блокировок чата */
    'chat_lock_status' => [
        'without_lock' => [
            'en' => 'Chat is unlocked for all users',
            'ru' => 'Чат разблокирован для всех пользователей',
            'uk' => 'Чат розблокований для всіх користувачів',
        ],
        'lock_add_message_only_customer' => [
            'en' => 'Chat is temporarily blocked for the Customer',
            'ru' => 'Чат временно заблокирован для Заказчика',
            'uk' => 'Чат тимчасово заблокований для Замовника',
        ],
        'lock_add_message_only_performer' => [
            'en' => 'Chat is temporarily blocked for the Traveler',
            'ru' => 'Чат временно заблокирован для Путешественника',
            'uk' => 'Чат тимчасово заблокований для Мандрівника',
        ],
        'lock_add_message_all' => [
            'en' => 'Chat is temporarily blocked',
            'ru' => 'Чат временно заблокирован',
            'uk' => 'Чат тимчасово заблоковано',
        ],
        'permit_one_message_only_customer' => [
            'en' => 'Customer, please reply in one message. Please note that you can only have one message after the chat will be temporarily blocked until further instructions from the moderator.',
            'ru' => 'Заказчик, дайте ответ в одном сообщении. Обращаем ваше внимание, что у вас есть только одно сообщение, после чат будет временно заблокирован до дальнейших инструкций от модератора.',
            'uk' => 'Замовник, дайте відповідь в одному повідомленні. Звертаємо вашу увагу, що у вас є лише одне повідомлення, після чату буде тимчасово заблоковано до подальших інструкцій від модератора.',
        ],
        'permit_one_message_only_performer' => [
            'en' => 'Traveler, give the answer in one message. Please note that you can only have one message after the chat will be temporarily blocked until further instructions from the moderator.',
            'ru' => 'Путешественник, дайте ответ в одном сообщении. Обращаем ваше внимание, что у вас есть только одно сообщение, после чат будет временно заблокирован до дальнейших инструкций от модератора.',
            'uk' => 'Мандрівник, дайте відповідь в одному повідомленні. Звертаємо вашу увагу, що у вас є лише одне повідомлення, після чату буде тимчасово заблоковано до подальших інструкцій від модератора.',
        ],
        'permit_one_message_all' => [
            'en' => 'Give the answer in one message. Please note that you can only have one message after the chat will be temporarily blocked until further instructions from the moderator.',
            'ru' => 'Дайте ответ в одном сообщении. Обращаем ваше внимание, что у вас есть только одно сообщение, после чат будет временно заблокирован, до дальнейших инструкций от модератора.',
            'uk' => 'Дайте відповідь в одному повідомленні. Звертаємо вашу увагу, що у вас є тільки одне повідомлення, після чату буде тимчасово заблоковано, до подальших інструкцій від модератора.',
        ],
    ],
    /* END Виды блокировок чата */

];
