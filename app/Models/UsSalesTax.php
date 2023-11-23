<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UsSalesTax
 *
 * @property string $code Код штату
 * @property string $name_en Назва штату англійською
 * @property string $name_uk Назва штату українською
 * @property string $name_ru Назва штату російською
 * @property string $tax_rate Максимальна ставка податку з місцевим/міським податком на продаж
 * @property \Illuminate\Support\Carbon $created_at Створено
 * @property \Illuminate\Support\Carbon|null $updated_at Змінено
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax query()
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax whereNameEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax whereNameRu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax whereNameUk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UsSalesTax whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UsSalesTax extends Model
{
    use TimestampSerializable;
    protected $table = 'us_sales_tax';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
}
