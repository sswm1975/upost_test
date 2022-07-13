<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\Constant
 *
 * @property int $id Код
 * @property string $name Наименование
 * @property string|null $value Значение
 * @property string|null $description Описание
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 * @method static \Illuminate\Database\Eloquent\Builder|Constant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Constant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Constant query()
 * @method static \Illuminate\Database\Eloquent\Builder|Constant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Constant whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Constant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Constant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Constant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Constant whereValue($value)
 * @mixin \Eloquent
 */
class Constant extends Model
{
    use TimestampSerializable;

    protected $fillable = ['name', 'value'];

    public static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::updateCacheConstants();
        });

        static::deleted(function () {
            static::updateCacheConstants();
        });
    }

    /**
     * Кэширование таблицы constants и занесение их в App Config, т.е. потом можно вызывать, к примеру config('default_language')
     */
    public static function initCache()
    {
        $constants = Cache::rememberForever('constants', function() {
            return static::getConstants();
        });

        config($constants);
    }

    public static function updateCacheConstants()
    {
        Cache::forget('constants');
        static::initCache();
    }

    public static function getConstants(): array
    {
        return static::pluck('value', 'name')->toArray();
    }
}
