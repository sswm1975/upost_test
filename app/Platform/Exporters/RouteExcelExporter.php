<?php

namespace App\Platform\Exporters;

use Illuminate\Support\Facades\DB;

class RouteExcelExporter extends AdminExcelExporter
{
    protected $fileName = 'Маршруты.xlsx';

    protected $columns = [
        'id'                => 'Код',
        'user_id'           => 'Код киента',
        'user_name'         => 'Фамилия и Имя клиента',
        'from_country_id'   => 'Страна откуда (код)',
        'from_country_name' => 'Страна откуда',
        'from_city_id'      => 'Город откуда (код)',
        'from_city_name'    => 'Город откуда',
        'to_country_id'     => 'Страна куда (код)',
        'to_country_name'   => 'Страна куда',
        'to_city_id'        => 'Город куда (код)',
        'to_city_name'      => 'Город куда',
        'deadline'          => 'Дата дедлайна',
        'status'            => 'Статус',
        'created_at'        => 'Добавлено',
        'updated_at'        => 'Изменено',
    ];

    public function query()
    {
        return parent::getQuery()
            ->withoutAppends()      # доп.поля не нужны
            ->setEagerLoads([])     # связи не подгружаем
            ->select([
                'id',
                'user_id',
                DB::raw('(SELECT CONCAT(surname, " ", name) FROM users WHERE id = routes.user_id) AS user_name'),
                'from_country_id',
                DB::raw('(SELECT name_ru FROM countries WHERE id = routes.from_country_id) AS from_country_name'),
                'from_city_id',
                DB::raw('(SELECT name_ru FROM cities WHERE id = routes.from_city_id) AS from_city_name'),
                'to_country_id',
                DB::raw('(SELECT name_ru FROM countries WHERE id = routes.to_country_id) AS to_country_name'),
                'to_city_id',
                DB::raw('(SELECT name_ru FROM cities WHERE id = routes.to_city_id) AS to_city_name'),
                'deadline',
                'status',
                'created_at',
                'updated_at',
            ]);
    }
}
