<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Category
 *
 * @property int $category_id Код
 * @property int $category_parent Код родителя
 * @property string|null $cat_name_uk Наименование на украинском
 * @property string|null $cat_name_ru Наименование на русском
 * @property string|null $cat_name_en Наименование на английском
 * @property string|null $cat_syn Описание
 * @method static Builder|Category language(string $lang = 'en')
 * @method static Builder|Category newModelQuery()
 * @method static Builder|Category newQuery()
 * @method static Builder|Category query()
 * @method static Builder|Category whereCatNameEn($value)
 * @method static Builder|Category whereCatNameRu($value)
 * @method static Builder|Category whereCatNameUk($value)
 * @method static Builder|Category whereCatSyn($value)
 * @method static Builder|Category whereCategoryId($value)
 * @method static Builder|Category whereCategoryParent($value)
 * @mixin \Eloquent
 */
class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    protected $fillable = ['cat_name_uk', 'cat_name_ru', 'cat_name_en'];
    public $timestamps = false;

    /**
     * Scope a query for selecting the column name depending on the specified language.
     *
     * @param Builder $query
     * @param string $lang
     * @return Builder
     */
    public function scopeLanguage(Builder $query, string $lang = 'en')
    {
        return $query->select('cat_name_' . $lang . ' as category_name');
    }
}
