<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
