<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Option
 *
 * @property int $option_id Код
 * @property string $option_name Валюта
 * @property string $option_val Курс
 * @method static \Illuminate\Database\Eloquent\Builder|Option newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Option newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Option query()
 * @method static \Illuminate\Database\Eloquent\Builder|Option rate($currency = 'usd')
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereOptionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereOptionVal($value)
 * @mixin \Eloquent
 */
class Option extends Model
{
    protected $table = 'options';
    protected $primaryKey = 'option_id';
    protected $guarded = ['option_id'];
    public $timestamps = false;

    public function scopeRate($query, $currency = 'usd')
    {
        return $query->where('option_name',  $currency)->first()->option_val ?? 1;
    }
}
