<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\OrderDeduction
 *
 * @property int $id Код
 * @property int $order_id Код заказа
 * @property string $type Тип вычета: tax_export - экспортный налог, tax_import - налог на импорт, fee - комиссии
 * @property string $name Наименование вычета
 * @property string $amount Сумма (в долларах)
 * @property \Illuminate\Support\Carbon $created_at Добавлено
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereType($value)
 * @mixin \Eloquent
 */
class OrderDeduction extends Model
{
    use TimestampSerializable;

    const TYPE_TAX_EXPORT = 'tax_export';
    const TYPE_TAX_IMPORT = 'tax_import';
    const TYPE_FEE = 'fee';

    const TAXES_TYPE = [
        self::TYPE_TAX_IMPORT,
        self::TYPE_TAX_EXPORT,
    ];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function scopeSumByOrder($query, int $order_id)
    {
        return $query
            ->whereOrderId($order_id)
            ->sum('amount');
    }

    public function scopeSumTaxesByOrder($query, int $order_id)
    {
        return $query
            ->whereOrderId($order_id)
            ->whereIn('type', [self::TYPE_TAX_EXPORT, self::TYPE_TAX_IMPORT])
            ->sum('amount');
    }

    public function scopeSumFeesByOrder($query, int $order_id)
    {
        return $query
            ->whereOrderId($order_id)
            ->where('type', self::TYPE_FEE)
            ->sum('amount');
    }
}
