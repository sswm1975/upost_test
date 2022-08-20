<?php

namespace App\Models\Traits;

trait WithoutAppends
{
    /**
     * Флаг, что в модель не нужно добавлять $appends атрибуты (исп. при выгрузке в эксель из админки)
     *
     * @var bool
     */
    public static bool $withoutAppends = false;

    /**
     * Список временных атрибутов, которые нужно добавить в запрос.
     *
     * @var array
     */
    protected static array $temp_appends = [];

    /**
     * Скоуп: В модель не добавлять доп.атрибуты массива $appends.
     *
     * @param $query
     * @param array $appends
     * @return mixed
     */
    public function scopeWithoutAppends($query, array $appends = [])
    {
        if (empty($appends)) {
            self::$withoutAppends = true;
        } else {
            self::$temp_appends = $appends;
        }

        return $query;
    }

    /**
     * Get all of the appendable values that are arrayable.
     *
     * @return array
     */
    protected function getArrayableAppends(): array
    {
        if (self::$withoutAppends) {
            return [];
        }

        if (!empty(self::$temp_appends)) {
            return self::$temp_appends;
        }

        return parent::getArrayableAppends();
    }
}
