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

    /**
     * Получить список всех стран.
     *
     * @return array
     */
    public static function getCountries(): array
    {
        return static::language(app()->getLocale())
            ->addSelect('country_id')
            ->oldest('country_id')
            ->get()
            ->toArray();
    }

    /**
     * Получить список всех стран или выбранной страны с городами.
     *
     * @param int $country_id
     * @return array
     */
    public static function getCountriesWithCities(int $country_id = 0): array
    {
        $countries = static::language(app()->getLocale())
            ->with('cities:country_id,city_id,city_name_' . app()->getLocale() . ' as city_name' )
            ->when($country_id, function ($query) use ($country_id) {
                return $query->where('country_id', $country_id);
            })
            ->addSelect('country_id')
            ->oldest('country_id')
            ->get()
            ->toArray();

        foreach ($countries as $country_key => $country) {
            foreach ($country['cities'] as $city_key => $city) {
                unset($city['country_id']);
                $countries[$country_key]['cities'][$city_key] = $city;
            }
        }

        return $countries;
    }
}
