<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    protected $fillable = ['cat_name_uk', 'cat_name_ru', 'cat_name_en'];
    public $timestamps = false;

    public function scopeLanguage($query, $lang = 'en')
    {
        return $query->select('cat_name_' . $lang . ' as name');
    }
}
