<?php

namespace App\Models;

use App\Modules\MapsGoogleApi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\City
 *
 * @property int $id Код
 * @property string $name_en Наименование на английском
 * @property string|null $name_uk Наименование на украинском
 * @property string|null $name_ru Наименование на русском
 * @property string|null $region Наименование региона (область, штат)
 * @property string $country_id Cтрана (ISO 3166-1 alpha-2 code)
 * @property-read \App\Models\Country $country
 * @method static Builder|City language()
 * @method static Builder|City newModelQuery()
 * @method static Builder|City newQuery()
 * @method static Builder|City query()
 * @method static Builder|City whereCountryId($value)
 * @method static Builder|City whereId($value)
 * @method static Builder|City whereNameEn($value)
 * @method static Builder|City whereNameRu($value)
 * @method static Builder|City whereNameUk($value)
 * @method static Builder|City whereRegion($value)
 * @mixin \Eloquent
 */
class City extends Model
{
    protected $table = 'cities';
    protected $primaryKey = 'id';
    protected $fillable  = ['country_id', 'name_en', 'name_uk', 'name_ru', 'region'];
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

        return $query->select(["name_$lang as name"]);
    }

    ### LINKS ###

    public function country(): BelongsTo
    {
        $lang = app()->getLocale();

        return $this->belongsTo(Country::class, 'country_id', 'id')
            ->select(['id', 'name_en', "name_{$lang} as name"]);
    }

    ### QUERIES ###

    /**
     * Получить код города по коду страны и названию города.
     * Несуществующая страна будет добавлена в таблицу, предварительно определив название страны на украинском и русском языках.
     *
     * @param string $country_id
     * @param string $region
     * @param string $name_en
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getId(string $country_id = null, string $region = null, string $name_en = null)
    {
        if (empty($country_id) || empty($name_en)) return null;

        $city_id = static::whereCountryId($country_id)->whereRegion($region)->whereNameEn($name_en)->value('id');

        if (empty($city_id)) {
            $name_uk = MapsGoogleApi::getCitiNameInLanguage($name_en, $region, $country_id, 'uk');
            $name_ru = MapsGoogleApi::getCitiNameInLanguage($name_en, $region, $country_id, 'ru');
            $city_id = static::insertGetId(compact('country_id', 'region', 'name_en', 'name_uk', 'name_ru'));
        }

        return $city_id;
    }
}
