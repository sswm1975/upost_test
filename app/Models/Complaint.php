<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $table = 'complaints';
    protected $primaryKey = 'id';
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
     * Получить список всех жалоб или выбранной жалобы.
     *
     * @param int $id
     * @return array
     */
    public static function getComplaints(int $id = 0): array
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
