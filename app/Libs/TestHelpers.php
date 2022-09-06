<?php

namespace App\Libs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestHelpers
{
    /**
     * Сбрасывание счетчика неудачных попыток для middleware('throttle:5,10') и локального IP 127.0.0.1
     *
     * @return void
     */
    public static function clearLoginAttempts()
    {
        # см. как определяется ключ в методе resolveRequestSignature класса Illuminate\Routing\Middleware\ThrottleRequests
        $ip = request()->ip();
        $domain = optional(request()->route())->getDomain();
        $key = sha1("{$domain}|{$ip}");

        # подключаемся к классу RateLimiter, который может сбрасывать счетчик
        app(\Illuminate\Cache\RateLimiter::class)->clear($key);
    }

    /**
     * Пересоздание и заполнение таблиц в тестовой БД (формирование структуры и заполнение данными с исходной таблицы).
     *
     * @return void
     */
    public static function createTables()
    {
        $tables = [
            'constants'   => true,
            'countries'   => true,
            'cities'      => true,
            'wait_ranges' => true,
            'currencies'  => true,
            'users'       => false,
            'orders'      => false,
            'routes'      => false
        ];

        # тестовое соединение
        $test_db = DB::connection('test');

        # удаляем все таблицы
        $drop_tables = implode(',', array_keys($tables));
        $test_db->statement("DROP TABLE IF EXISTS {$drop_tables};");

        # в тестовой БД создаем таблицы и копируем исходные данные при включенном признаке
        foreach ($tables as $table => $copy_data) {
            $test_db->statement("CREATE TABLE {$table} LIKE upost.{$table};");

            if ($copy_data) {
                $test_db->statement("INSERT INTO {$table} SELECT * FROM upost.{$table};");
            }
        }
    }
}
