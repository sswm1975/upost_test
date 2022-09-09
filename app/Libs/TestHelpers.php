<?php

namespace App\Libs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

DEFINE('GEN_PASSWORD_OK', md5(md5('testtest')));

class TestHelpers
{

    /* Действительные логины и пароль */
    const LOGIN_EMAIL_OK = 'sswm@i.ua';
    const LOGIN_PHONE_OK = '+380978820043';
    const PASSWORD_OK =  GEN_PASSWORD_OK;

    /* Фиктивный логин и пароль */
    const LOGIN_FAIL =  'test';
    const PASSWORD_FAIL = 123456;

    /* Тестируемые конечные точки */
    const LOGIN_URI          = '/api/auth/login';    # аутентификации по емейлу или телефону
    const LOGIN_SOCIAL_URI   = '/api/auth/social';   # аутентификации через социальную сеть: Google или Facebook
    const LOGOUT_URI         = '/api/auth/logout';   # завершения сеанса авторизованного пользователя
    const REGISTER_URI       = '/api/auth/register'; # регистрация пользователя

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

    /**
     * Функция формирования возможных комбинаций массива.
     * (взято с https://ru.stackoverflow.com/questions/955897/возможные-комбинации-массива-php)
     *
     * @param array $arr
     * @param array $res
     * @param array $prefix
     * @param int $offset
     */
    public static function tuples(array $arr, array &$res, array $prefix = [], int $offset = 0)
    {
        for ($i = $offset; $i < count($arr); $i++) {
            $nextPrfx = array_merge($prefix, [$arr[$i]]);
            array_push($res, $nextPrfx);
            static::tuples($arr, $res, $nextPrfx, ++$offset);
        }
    }
}
