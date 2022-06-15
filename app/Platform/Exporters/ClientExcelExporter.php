<?php

namespace App\Platform\Exporters;

use Illuminate\Support\Facades\DB;

class ClientExcelExporter extends AdminExcelExporter
{
    protected $fileName = 'Клиенты.xlsx';

    protected $columns = [
        'id'                    => 'Код',
        'surname'               => 'Фамилия',
        'name'                  => 'Имя',
        'phone'                 => 'Телефон',
        'email'                 => 'Емейл',
        'gender'                => 'Пол',
        'birthday'              => 'Дата рождения',
        'city_name'             => 'Город',
        'wallet'                => 'Баланс',
        'currency'              => 'Валюта',
        'lang'                  => 'Язык',
        'card_number'           => '№ карточки',
        'card_name'             => 'Имя на карте',
        'status'                => 'Статус',
        'validation'            => 'Валидация',
        'scores_count'          => 'К-во баллов',
        'reviews_count'         => 'К-во отзывов',
        'rating'                => 'Рейтинг',
        'failed_delivery_count' => 'К-во неуд.доставок',
        'failed_receive_count'  => 'К-во неуд.получений',
        'last_active'           => 'Последняя активность',
        'register_date'         => 'Дата регистрации',
        'created_at'            => 'Добавлено',
        'updated_at'            => 'Изменено',
    ];

    public function query()
    {
        return parent::query()
            ->withoutAppends()
            ->join('cities', 'cities.id', 'users.city_id')
            ->select([
                'users.id',
                'users.name',
                'users.surname',
                DB::raw('CONCAT(" ", users.phone) AS phone'),
                'users.email',
                'users.gender',
                'users.birthday',
                'cities.name_ru AS city_name',
                'users.wallet',
                'users.currency',
                'users.lang',
                DB::raw('CONCAT(" ", users.card_number) AS card_number'),
                'users.card_name',
                'users.status',
                'users.validation',
                'users.scores_count',
                'users.reviews_count',
                DB::raw('IF(users.reviews_count > 0, ROUND(users.scores_count / users.reviews_count, 2), 0) AS rating'),
                'users.failed_delivery_count',
                'users.failed_receive_count',
                'users.last_active',
                'users.register_date',
                'users.created_at',
                'users.updated_at',
            ]);
    }
}
