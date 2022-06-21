<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\OrderCalculation
 *
 * @property int $id Код
 * @property int $order_id Код заказа
 * @property string $type Тип
 * @property string $name Наименование
 * @property string $amount Сумма
 * @property \Illuminate\Support\Carbon $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCalculation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrderCalculation extends Model
{
    use TimestampSerializable;
}
