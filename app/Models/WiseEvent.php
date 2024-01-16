<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\WiseEvent
 *
 * @property int $id Код
 * @property string|null $event_type Тип події
 * @property int|null $transfer_id Код трансферу
 * @property int|null $profile_id Код профілю
 * @property int|null $account_id Код рахунку
 * @property string|null $state Стан
 * @property array $event Подія
 * @property string $status Статус обробки
 * @property \Illuminate\Support\Carbon $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Оновлено
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\WiseResource[] $wise_resources
 * @property-read int|null $wise_resources_count
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WiseEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WiseEvent extends Model
{
    use TimestampSerializable;

    protected $guarded =['id'];
    protected $casts = ['event' => 'array'];
    public $timestamps = false;
    protected $dates = ['created_at', 'updated_at'];

    # Типы Wise событий
    const EVENT_TYPE_TRANSFER_STATE_CHANGE = 'transfer-state-change';
    const EVENT_TYPE_TRANSFER_PAYOUT_FAILURE = 'transfer-payout-failure';

    # Статусы обработки события
    const STATUS_NEW = 'new';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_PROCESSED = 'processed';

    # Состояние Wise-трансферу для обработки события
    const STATE_FOR_PROCESSING = 'waiting_recipient_input_to_proceed';

    ### RELATIONS ###

    public function wise_resources(): BelongsToMany
    {
        return $this->belongsToMany(WiseResource::class);
    }
}
