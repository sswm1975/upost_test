<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use App\Events\NoticeEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\Notice
 *
 * @property int $id Код
 * @property int $user_id Код пользователя
 * @property string $notice_type Тип уведомления
 * @property bool $is_read Уведомление прочитано?
 * @property int|null $object_id Код объекта инициирующего событие
 * @property array|null $data Данные
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read string $created_at_format1
 * @property-read string $created_at_format2
 * @property-read string $created_at_format3
 * @property-read \App\Models\NoticeType|null $type
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Notice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice owner()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice query()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereNoticeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereUserId($value)
 * @mixin \Eloquent
 */
class Notice extends Model
{
    use TimestampSerializable;

    protected $guarded = ['id'];
    protected $casts = [
        'is_read' => 'boolean',
        'data'    => 'array',
    ];
    # даты создания уведомления в разных форматах, так попросил Артем для фронта %-)
    protected $appends = [
        'created_at_format1',  # D MMM YYYY H:mm
        'created_at_format2',  # D MMM YYYY
        'created_at_format3',  # D-MM-YYYY
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            try {
                broadcast(new NoticeEvent($model));
            } catch (\Exception $e) {
                \Log::error('Notice broadcast error');
                \Log::error($e->getMessage());
            }
        });
    }

    ### GETTERS ###

    public function getCreatedAtFormat1Attribute(): string
    {
        return $this->created_at->isoFormat('D MMM YYYY H:mm');
    }

    public function getCreatedAtFormat2Attribute(): string
    {
        return $this->created_at->isoFormat('D MMM YYYY');
    }

    public function getCreatedAtFormat3Attribute(): string
    {
        return $this->created_at->isoFormat('D-MM-YYYY');
    }

    ### RELATIONS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function type(): HasOne
    {
        return $this->hasOne(NoticeType::class, 'id', 'notice_type');
    }

    ### SCOPES ###

    public function scopeOwner($query)
    {
        return $query->where('user_id', request()->user()->id);
    }
}
