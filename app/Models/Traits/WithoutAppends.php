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
     * Скоуп: В модель не добавлять доп.атрибуты массива $appends.
     *
     * @param $query
     * @return mixed
     */
    public function scopeWithoutAppends($query)
    {
        self::$withoutAppends = true;

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

        return parent::getArrayableAppends();
    }

}
