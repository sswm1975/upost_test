<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\Currency
 *
 * @property string $id Код
 * @property string $symbol 3-х буквенный код валюты
 * @property int $code Код валюты
 * @property string $rate Курс
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @method static \Illuminate\Database\Eloquent\Builder|Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency query()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Currency extends Model
{
    use TimestampSerializable;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    public static function initCache()
    {
        $rates = Cache::rememberForever('rates', function() {
            return static::getRates();
        });

        config(['rates' => $rates]);
    }

    public static function updateCache()
    {
        Cache::forget('rates');

        static::initCache();
    }

    public static function getRates(): array
    {
        return static::pluck('rate', 'id')->toArray();
    }
}
