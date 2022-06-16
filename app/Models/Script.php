<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Script
 *
 * @property int $id Код
 * @property string $name Наименование
 * @property string $alias Алиас
 * @property string $code PHP-код
 * @property string $description Описание
 * @property int $active Действует (да/нет)
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Country[] $from_countries
 * @property-read int|null $from_countries_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Country[] $to_countries
 * @property-read int|null $to_countries_count
 * @method static \Illuminate\Database\Eloquent\Builder|Script newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Script newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Script query()
 * @method static \Illuminate\Database\Eloquent\Builder|Script whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Script whereAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Script whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Script whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Script whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Script whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Script whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Script whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Script extends Model
{
    use TimestampSerializable;

    protected $attributes = ['active' => VALUE_ACTIVE];

    public function from_countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'script_from_country');
    }

    public function to_countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'script_to_country');
    }
}
