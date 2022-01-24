<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use TimestampSerializable;

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = strtolower($value);
    }

    /**
     * Получить список магазинов по списку слагов.
     *
     * @param array $slugs
     * @return array
     */
    public static function getBySlugs(array $slugs = []): array
    {
        if (empty($slugs)) return [];

        return static::whereIn('slug', $slugs)
            ->pluck('name', 'slug')
            ->toArray();
    }
}
