<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class WaitRange extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'wait_ranges';
    protected $primaryKey = 'id';
    protected $guarded  = ['id'];
    public $timestamps = false;

    /**
     * см. https://packagist.org/packages/spatie/eloquent-sortable
     */
    public array $sortable = [
        'order_column_name' => 'order',
        'sort_when_creating' => true,
    ];

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
