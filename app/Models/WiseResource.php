<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\WiseResource
 *
 * @property int $id Код
 * @property int $wise_event_id Код події
 * @property string|null $type Тип ресурсу (Трансфер/Профіль/Рахунок)
 * @property int $resource_id Код ресурсу
 * @property array $resource JSON ресурсу
 * @property string|null $key Головний ключ з ресурсу
 * @property string|null $value Значення ключа з ресурсу
 * @property \Illuminate\Support\Carbon $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Оновлено
 * @property-read \App\Models\Withdrawal|null $wise_event
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource query()
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereResource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseResource whereWiseEventId($value)
 * @mixin \Eloquent
 */
class WiseResource extends Model
{
    use TimestampSerializable;

    public $timestamps = false;
    protected $guarded =['id'];
    protected $casts = ['resource' => 'array'];
    protected $dates = ['created_at', 'updated_at'];

    const TYPE_TRANSFER = 'transfer';
    const TYPE_PROFILE = 'profile';
    const TYPE_ACCOUNT = 'account';

    ### RELATIONS ###

    public function wise_event(): HasOne
    {
        return $this->hasOne(Withdrawal::class, 'wise_event_id','id');
    }
}
