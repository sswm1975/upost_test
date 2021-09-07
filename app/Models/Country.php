<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Country
 *
 * @property int $country_id Код
 * @property string|null $country_name_uk Наименование на украинском
 * @property string|null $country_name_ru Наименование на русском
 * @property string|null $country_name_en Наименование на английском
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\City[] $cities
 * @property-read int|null $cities_count
 * @method static Builder|Country language(string $lang = 'en')
 * @method static Builder|Country newModelQuery()
 * @method static Builder|Country newQuery()
 * @method static Builder|Country query()
 * @method static Builder|Country whereCountryId($value)
 * @method static Builder|Country whereCountryNameEn($value)
 * @method static Builder|Country whereCountryNameRu($value)
 * @method static Builder|Country whereCountryNameUk($value)
 * @mixin \Eloquent
 */
class Country extends Model
{
    protected $table = 'country';
    protected $primaryKey = 'country_id';
    protected $fillable  = ['country_name_uk', 'country_name_ru', 'country_name_en'];
    public $timestamps = false;

    /**
     * @return HasMany
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_id');
    }

    /**
     * Scope a query for selecting the column name depending on the specified language.
     *
     * @param Builder $query
     * @param string $lang
     * @return Builder
     */
    public function scopeLanguage(Builder $query, string $lang = 'en')
    {
        return $query->select('country_name_' . $lang . ' as country_name');
    }
}
