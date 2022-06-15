<?php

namespace App\Platform\Exporters;

use Illuminate\Support\Facades\DB;

class OrderExcelExporter extends AdminExcelExporter
{
    protected $fileName = 'Заказы.xlsx';

    protected $columns = [
        'id'                => 'Код',
        'user_id'           => 'Код киента',
        'user_name'         => 'Фамилия и Имя клиента',
        'name'              => 'Наименование товара',
        'slug'              => 'Слаг',
        'product_link'      => 'Ссылка на товар',
        'shop_slug'         => 'Магазин',
        'products_count'    => 'Кол-во',
        'price'             => 'Цена',
        'currency'          => 'Валюта',
        'price_usd'         => 'Цена в $',
        'user_price'        => 'Доход',
        'user_currency'     => 'Валюта',
        'user_price_usd'    => 'Доход в $',
        'not_more_price'    => 'Выше не принимать',
        'from_country_id'   => 'Страна откуда (код)',
        'from_country_name' => 'Страна откуда',
        'from_city_id'      => 'Город откуда (код)',
        'from_city_name'    => 'Город откуда',
        'to_country_id'     => 'Страна куда (код)',
        'to_country_name'   => 'Страна куда',
        'to_city_id'        => 'Город куда (код)',
        'to_city_name'      => 'Город куда',
        'register_date'     => 'Зарегистрирован',
        'wait_range_name'   => 'Готов ждать',
        'deadline'          => 'Дата дедлайна',
        'looks'             => 'Просмотров',
        'strikes'           => 'Жалобы',
        'status'            => 'Статус',
        'created_at'        => 'Добавлено',
        'updated_at'        => 'Изменено',
    ];

    public function query()
    {
        return parent::query()
            ->withoutAppends()
            ->select([
                'id',
                'user_id',
                DB::raw('(SELECT CONCAT(surname, " ", name) FROM users WHERE users.id = orders.user_id) AS user_name'),
                'name',
                'slug',
                'product_link',
                'shop_slug',
                'products_count',
                'price',
                'currency',
                'price_usd',
                'user_price',
                'user_currency',
                'user_price_usd',
                DB::raw('IF(not_more_price = 1, "Да", "Нет") AS not_more_price'),
                'from_country_id',
                DB::raw('(SELECT name_ru FROM countries WHERE countries.id = orders.from_country_id) AS from_country_name'),
                'from_city_id',
                DB::raw('(SELECT name_ru FROM cities WHERE cities.id = orders.from_city_id) AS from_city_name'),
                'to_country_id',
                DB::raw('(SELECT name_ru FROM countries WHERE countries.id = orders.to_country_id) AS to_country_name'),
                'to_city_id',
                DB::raw('(SELECT name_ru FROM cities WHERE cities.id = orders.to_city_id) AS to_city_name'),
                'register_date',
                DB::raw('(SELECT name_ru FROM wait_ranges WHERE wait_ranges.id = orders.wait_range_id) AS wait_range_name'),
                'deadline',
                'looks',
                'strikes',
                'status',
                'created_at',
                'updated_at',
            ]);
    }
}
