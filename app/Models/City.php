<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\City
 *
 * @property int $city_id Код
 * @property int $country_id Код страны
 * @property string|null $city_name_uk Наименование на украинском
 * @property string|null $city_name_ru Наименование на русском
 * @property string|null $city_name_en Наименование на английском
 * @method static \Illuminate\Database\Eloquent\Builder|City language($lang = 'en')
 * @method static \Illuminate\Database\Eloquent\Builder|City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|City query()
 * @method static \Illuminate\Database\Eloquent\Builder|City whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereCityNameEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereCityNameRu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereCityNameUk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereCountryId($value)
 * @mixin \Eloquent
 */
class City extends Model
{
    protected $table = 'city';
    protected $primaryKey = 'city_id';
    protected $fillable  = ['city_name_uk', 'city_name_ru', 'city_name_en'];
    public $timestamps = false;

    public function scopeLanguage($query, $lang = 'en')
    {
        return $query->select('city_name_' . $lang . ' as city_name');
    }
}
