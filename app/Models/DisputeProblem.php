<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DisputeProblem
 *
 * @property int $id Код
 * @property string $name_uk Наименование на украинском
 * @property string $name_ru Наименование на русском
 * @property string $name_en Наименование на английском
 * @property int $days Количество дней
 * @property int $active Действует (да/нет)
 * @method static Builder|DisputeProblem active()
 * @method static Builder|DisputeProblem language()
 * @method static Builder|DisputeProblem newModelQuery()
 * @method static Builder|DisputeProblem newQuery()
 * @method static Builder|DisputeProblem query()
 * @method static Builder|DisputeProblem whereActive($value)
 * @method static Builder|DisputeProblem whereDays($value)
 * @method static Builder|DisputeProblem whereId($value)
 * @method static Builder|DisputeProblem whereNameEn($value)
 * @method static Builder|DisputeProblem whereNameRu($value)
 * @method static Builder|DisputeProblem whereNameUk($value)
 * @mixin \Eloquent
 */
class DisputeProblem extends Model
{
    protected $table = 'dispute_problems';
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
     * Активные проблемы.
     *
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Получить справочник проблем для спора или выбранной проблемы.
     *
     * @param int $id
     * @return array
     */
    public static function getList(int $id = 0): array
    {
        return static::query()
            ->when(!empty($id), function ($query) use ($id) {
                return $query->whereKey($id);
            })
            ->language()
            ->addSelect('id')
            ->get()
            ->toArray();
    }
}
