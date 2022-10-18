<?php

namespace App\Models;

use App\Events\NoticeEvent;
use Carbon\Carbon;
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
 * @property string $created_at Добавлено
 * @property string $updated_at Изменено
 * @property-read \App\Models\NoticeType|null $type
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Notice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice owner()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice query()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice setIsoFormatDate(string $iso_format_date = '')
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
    protected $guarded = ['id'];
    protected $casts = [
        'is_read' => 'boolean',
        'data'    => 'array',
    ];

    # формат даты в стиле MySQL
    public static string $iso_format_date = 'YYYY-MM-DD HH:mm:ss';

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

    public function getCreatedAtAttribute($value): string
    {
        return (new Carbon($value))->isoFormat(self::$iso_format_date);
    }

    public function getUpdatedAtAttribute($value): string
    {
        return (new Carbon($value))->isoFormat(self::$iso_format_date);
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

    /**
     * Установить формат для полей с датой.
     *
     * @param $query
     * @param string $iso_format_date
     * @return mixed
     */
    public function scopeSetIsoFormatDate($query, string $iso_format_date = '')
    {
        self::$iso_format_date = $iso_format_date;

        return $query;
    }
}
