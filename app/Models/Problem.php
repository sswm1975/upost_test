<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    protected $fillable = ['name_uk', 'name_ru', 'name_en'];
    public $timestamps = false;

    /**
     * Scope a query for selecting the column name depending on the specified language.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLanguage(Builder $query): Builder
    {
        $lang = app()->getLocale();

        return $query->select("name_$lang as name");
    }

    /**
     * Получить список всех проблем или выбранной проблемы.
     *
     * @param int $id
     * @return array
     */
    public static function getProblems(int $id = 0): array
    {
        return static::query()
            ->when(!empty($id), function ($query) use ($id) {
                return $query->whereKey($id);
            })
            ->language()
            ->addSelect('id')
            ->oldest('id')
            ->get()
            ->toArray();
    }
}
