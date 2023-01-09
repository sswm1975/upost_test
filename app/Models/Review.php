<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Review
 *
 * @property int $id Код
 * @property int $rate_id Ставка
 * @property int $user_id Автор отзыва
 * @property int $recipient_id Получатель отзыва
 * @property string|null $recipient_type Тип получателя (Заказчик/Исполнитель)
 * @property int $scores Оценка
 * @property string $text Текст отзыва
 * @property \Illuminate\Support\Carbon $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read \App\Models\User $author
 * @property-read \App\Models\Rate $rate
 * @property-read \App\Models\User $recipient
 * @method static \Illuminate\Database\Eloquent\Builder|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Review owner()
 * @method static \Illuminate\Database\Eloquent\Builder|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereRecipientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereRecipientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereScores($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereUserId($value)
 * @mixin \Eloquent
 */
class Review extends Model
{
    use TimestampSerializable;

    protected $guarded = ['id'];
    public $timestamps = false;
    protected $dates = ['created_at', 'updated_at'];

    const USER_TYPE_CUSTOMER = 'customer';
    const USER_TYPE_PERFORMER = 'performer';

    ### SETTERS ###

    public function setTextAttribute($value)
    {
        $this->attributes['text'] = !empty($value) ? strip_tags(strip_unsafe($value)) : null;
    }

    ### RELATIONS ###

    /**
     * Автор отзыва.
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Получатель отзыва.
     *
     * @return BelongsTo
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Отзыв по ставке.
     *
     * @return BelongsTo
     */
    public function rate(): BelongsTo
    {
        return $this->belongsTo(Rate::class);
    }

    ### SCOPES ###

    /**
     * Добавляем условие, что авторизированный пользователь является владельцем отзыва.
     *
     * @param $query
     * @return mixed
     */
    public function scopeOwner($query)
    {
        return $query->where('user_id', request()->user()->id);
    }
}
