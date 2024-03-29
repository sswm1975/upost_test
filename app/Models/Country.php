<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Country
 *
 * @property string $id ISO 3166-1 alpha-2 code
 * @property string $alpha3 ISO 3166-1 alpha-3 code
 * @property string $code ISO 3166-1 num-3 code
 * @property string $name_en Наименование на английском
 * @property string $name_uk Наименование на украинском
 * @property string $name_ru Наименование на русском
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\City[] $cities
 * @property-read int|null $cities_count
 * @method static Builder|Country language()
 * @method static Builder|Country newModelQuery()
 * @method static Builder|Country newQuery()
 * @method static Builder|Country query()
 * @method static Builder|Country whereAlpha3($value)
 * @method static Builder|Country whereCode($value)
 * @method static Builder|Country whereId($value)
 * @method static Builder|Country whereNameEn($value)
 * @method static Builder|Country whereNameRu($value)
 * @method static Builder|Country whereNameUk($value)
 * @mixin \Eloquent
 */
class Country extends Model
{
    protected $table = 'countries';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * @return HasMany
     */
    public function cities(): HasMany
    {
        $lang = app()->getLocale();

        return $this->hasMany(City::class, 'country_id')
            ->select(['id', "name_{$lang} as name", 'country_id']);
    }

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
     * Получить список всех стран.
     *
     * @param string $country_id
     * @return array
     */
    public static function getCountries(string $country_id = null): array
    {
        return static::query()
            ->when(!empty($country_id), function ($query) use ($country_id) {
                return $query->whereKey($country_id);
            })
            ->language()
            ->addSelect('id')
            ->oldest('id')
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
        return static::with('cities')
            ->when($country_id, function ($query) use ($country_id) {
                return $query->whereKey($country_id);
            })
            ->language()
            ->addSelect('id')
            ->oldest('id')
            ->get()
            ->toArray();
    }

    /**
     * Инкрементный поиск по странам.
     *
     * @param string $search
     * @return array
     */
    public static function filterByCountryName(string $search): array
    {
        $counties = static::where(function($q) use ($search) {
               $q->where('name_uk', 'like', "%$search%")->orWhere('name_ru', 'like', "%$search%")->orWhere('name_en', 'like', "%$search%");
            })
            ->language()
            ->addSelect('id')
            ->oldest('id')
            ->get()
            ->toArray();

        foreach ($counties as $key => $county) {
            $counties[$key]['cities'] = [];
        }

        return $counties;
    }

    /**
     * Инкрементный поиск по городам.
     *
     * @param string $search
     * @return array
     */
    public static function filterByCityName(string $search): array
    {
        return static::with(['cities' => function($q) use ($search) {
                $q->where('name_uk', 'like', "%$search%")->orWhere('name_ru', 'like', "%$search%")->orWhere('name_en', 'like', "%$search%");
            }])
            ->whereHas('cities', function($q) use ($search) {
                $q->where('name_uk', 'like', "%$search%")->orWhere('name_ru', 'like', "%$search%")->orWhere('name_en', 'like', "%$search%");
            })
            ->language()
            ->addSelect('id')
            ->oldest('id')
            ->get()
            ->toArray();
    }
}
