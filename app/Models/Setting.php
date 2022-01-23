<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use TimestampSerializable;

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
}
