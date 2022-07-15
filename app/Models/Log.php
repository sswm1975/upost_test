<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Log
 *
 * @property int $id Код
 * @property string $time Дата и время запроса
 * @property string $duration Продолжительность от запуска Laravel до отдачи ответа (в секундах)
 * @property string $duration_request Продолжительность от формирования запроса до отдачи ответа (в секундах)
 * @property string|null $ip IP-адрес
 * @property string|null $prefix Префикс маршрута
 * @property string|null $url Ссылка
 * @property string|null $method Метод
 * @property array|null $input Параметры запроса
 * @property array|null $output JSON-ответ от сервера
 * @property array|null $server Серверные переменные
 * @property array|null $queries SQL-запросы
 * @method static \Illuminate\Database\Eloquent\Builder|Log newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Log newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Log query()
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereDurationRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereInput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereOutput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereQueries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereUrl($value)
 * @mixin \Eloquent
 */
class Log extends Model
{
    public $timestamps = false;
    protected $casts = [
        'input'   => 'array',
        'output'  => 'array',
        'server'  => 'array',
        'queries' => 'array',
    ];
}
