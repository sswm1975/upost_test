<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use TimestampSerializable;

    protected $guarded = ['id'];
    public $timestamps = false;

    const USER_TYPE_CUSTOMER = 'customer';
    const USER_TYPE_PERFORMER = 'performer';

    ### BOOT MODEL ###

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->id;
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
    }

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
