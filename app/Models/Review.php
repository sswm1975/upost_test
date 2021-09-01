<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property boolean review_type
 */
class Review extends Model
{
    public $timestamps = false;

    public const TYPE_CREATOR = 0;
    public const TYPE_FREELANCER = 1;

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

    /**
     *
     * @return array|mixed
     * @var mixed
     */
    public static function getReviewTypeNames($type = null)
    {
        $list = [
            self::TYPE_CREATOR    => __('message.type_creator'),
            self::TYPE_FREELANCER => __('message.type_freelancer'),
        ];

        return ($type !== null) && in_array($type, [
            self::TYPE_CREATOR,
            self::TYPE_FREELANCER
        ]) ? $list[$type] : $list ;
    }



    //
    protected $fillable = ['user_id', 'job_id', 'rating', 'comment', 'review_type'];

    public function getReviewTypeAttribute($value)
    {
        return static::getReviewTypeNames($value);
    }

    /**
     * Получить количество отзывов по выбранному пользователю и типу пользователя.
     *
     * @param int $user_id
     * @param int|null $type
     * @return int
     */
    public static function getCountReviews(int $user_id, int $type = null): int
    {
        return static::whereUserId($user_id)
            ->when(!is_null($type), function($query) use ($type) {
                $query->whereReviewType($type);
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
            ->first(['rating', 'comment', 'created_at']);
    }
}
