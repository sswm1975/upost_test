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
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDeduction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrderDeduction extends Model
{
    use TimestampSerializable;
}
