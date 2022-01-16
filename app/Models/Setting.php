<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['name', 'value'];

    public static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::updateCacheSettings();
        });

        static::deleted(function () {
            static::updateCacheSettings();
        });
    }

    public static function initCache()
    {
        $settings = Cache::rememberForever('settings', function() {
            return static::getSettings();
        });

        config($settings);
    }

    public static function updateCacheSettings()
    {
        Cache::forget('settings');
        static::initCache();
    }

    public static function getSettings(): array
    {
        return static::pluck('value', 'name')->toArray();
    }

    /**
     * Prepare a date for array / JSON serialization.
     * https://laravel.com/docs/7.x/upgrade#date-serialization
     *
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
