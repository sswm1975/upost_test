<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\City
 *
 * @property int $id Код
 * @property int $country_id Код страны
 * @property string $name_uk Наименование на украинском
 * @property string $name_ru Наименование на русском
 * @property string $name_en Наименование на английском
 * @method static Builder|City language()
 * @method static Builder|City newModelQuery()
 * @method static Builder|City newQuery()
 * @method static Builder|City query()
 * @method static Builder|City whereCountryId($value)
 * @method static Builder|City whereId($value)
 * @method static Builder|City whereNameEn($value)
 * @method static Builder|City whereNameRu($value)
 * @method static Builder|City whereNameUk($value)
 * @mixin \Eloquent
 */
class City extends Model
{
    protected $table = 'cities';
    protected $primaryKey = 'id';
    protected $fillable  = ['name_uk', 'name_ru', 'name_en'];
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
