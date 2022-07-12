<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
