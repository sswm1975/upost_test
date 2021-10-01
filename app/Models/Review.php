<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    public const TYPE_CREATOR = 'creator';
    public const TYPE_FREELANCER = 'freelancer';

    public $timestamps = false;
    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
    }

    public function getTypeAttribute($value)
    {
        return __("message.type_{$value}");
    }

    /**
     * Связь с заказом или маршрутом.
     *
     * @return MorphTo
     */
    public function reviewable()
    {
        return $this->morphTo();
    }

    /**
     * Автор отзыва.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
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
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Получить количество отзывов по выбранному пользователю и типу пользователя.
     *
     * @param int $user_id
     * @param string|null $type
     * @return int
     */
    public static function getCountReviews(int $user_id, string $type = null): int
    {
        return static::whereUserId($user_id)
            ->when(!is_null($type), function($query) use ($type) {
                $query->whereType($type);
            })
            ->count();
    }

    /**
     * Получить количество отзывов по выбранному пользователю с типом "Заказчик".
     *
     * @param int $user_id
     * @return int
     */
    public static function getCountReviewsByCreator(int $user_id): int
    {
        return static::getCountReviews($user_id, self::TYPE_CREATOR);
    }

    /**
     * Получить количество отзывов по выбранному пользователю с типом "Исполнитель".
     *
     * @param int $user_id
     * @return int
     */
    public static function getCountReviewsByFreelancer(int $user_id): int
    {
        return static::getCountReviews($user_id, self::TYPE_FREELANCER);
    }

    /**
     * Получить последний отзыв пользователя.
     *
     * @param int $user_id
     * @return mixed
     */
    public static function getLastReview(int $user_id)
    {
        return static::whereUserId($user_id)
            ->latest()
            ->first(['rating', 'comment', 'created_at']) ?? [];
    }
}
