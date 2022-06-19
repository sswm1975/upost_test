<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CurrencyRate
 *
 * @property int $id Код
 * @property string $date Дата
 * @property string $currency_id Валюта
 * @property string $rate Текущий курс
 * @property string $created_at Добавлено
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate getMaxDate4Rate()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate query()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyRate whereRate($value)
 * @mixin \Eloquent
 */
class CurrencyRate extends Model
{
    protected $table = 'currency_rates';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;

    /**
     * Последняя дата с курсами.
     *
     * @return string Дата в формате YYYY-MM-DD
     */
    public function scopeGetMaxDate4Rate($query): string
    {
        return $query->max('date') ?? date('Y-m-d', strtotime('now -1 day'));
    }
}
