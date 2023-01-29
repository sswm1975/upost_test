<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Tax
 *
 * @property int $id Код
 * @property string $name Наименование
 * @property string $alias Алиас
 * @property string $code PHP-код
 * @property string $description Описание
 * @property int $active Действует (да/нет)
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Country[] $export_countries
 * @property-read int|null $export_countries_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Country[] $import_countries
 * @property-read int|null $import_countries_count
 * @method static \Illuminate\Database\Eloquent\Builder|Tax active()
 * @method static \Illuminate\Database\Eloquent\Builder|Tax export(string $country_id)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax import(string $country_id)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tax newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tax query()
 * @method static \Illuminate\Database\Eloquent\Builder|Tax taxesForRelation(string $relation, string $country_id)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax whereAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tax whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Tax extends Model
{
    use TimestampSerializable;

    protected $attributes = ['active' => VALUE_ACTIVE];

    ### LINKS ###

    public function export_countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'tax_export');
    }

    public function import_countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'tax_import');
    }

    ### SCOPES ###

    /**
     * Только действующие налоги.
     *
     * @param $query
     */
    public function scopeActive($query)
    {
        $query->whereActive(VALUE_ACTIVE);
    }

    /**
     * Действующие налоги для выбранной страны и направления.
     *
     * @param $query
     * @param string $relation  Типы связей: export_countries / import_countries
     * @param string $country_id   Код страны
     * @return mixed
     */
    public function scopeTaxesForRelation($query, string $relation, string $country_id)
    {
        return $query->active()
            ->whereHas($relation, function($q) use ($country_id) {
                $q->where('country_id', $country_id);
            })
            ->pluck('code', 'alias');
    }

    /**
     * Действующие налоги экспорта для выбранной страны.
     *
     * @param $query
     * @param string $country_id Код страны
     * @return mixed
     */
    public function scopeExport($query, string $country_id)
    {
        return $query->taxesForRelation('export_countries', $country_id);
    }

    /**
     * Действующие налоги импорта для выбранной страны.
     *
     * @param $query
     * @param string $country_id Код страны
     * @return mixed
     */
    public function scopeImport($query, string $country_id)
    {
        return $query->taxesForRelation('import_countries', $country_id);
    }
}
