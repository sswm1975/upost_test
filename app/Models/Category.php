<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Category
 *
 * @property int $id Код
 * @property int $parent_id Код родителя
 * @property string $name_uk Наименование на украинском
 * @property string $name_ru Наименование на русском
 * @property string $name_en Наименование на английском
 * @property string|null $description Описание
 * @method static Builder|Category language()
 * @method static Builder|Category newModelQuery()
 * @method static Builder|Category newQuery()
 * @method static Builder|Category query()
 * @method static Builder|Category whereDescription($value)
 * @method static Builder|Category whereId($value)
 * @method static Builder|Category whereNameEn($value)
 * @method static Builder|Category whereNameRu($value)
 * @method static Builder|Category whereNameUk($value)
 * @method static Builder|Category whereParentId($value)
 * @mixin \Eloquent
 */
class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $fillable = ['name_uk', 'name_ru', 'name_en', 'description'];
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
     * Получить список всех категорий или выбранной категории.
     *
     * @param int $category_id
     * @return array
     */
    public static function getCategories(int $category_id = 0): array
    {
        return static::query()
            ->when(!empty($category_id), function ($query) use ($category_id) {
                return $query->whereKey($category_id);
            })
            ->language()
            ->addSelect('id')
            ->oldest('id')
            ->get()
            ->toArray();
    }
}
