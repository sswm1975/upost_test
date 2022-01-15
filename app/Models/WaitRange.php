<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WaitRange extends Model
{
    protected $table = 'wait_ranges';
    protected $primaryKey = 'id';
    protected $guarded  = ['id'];
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
}
