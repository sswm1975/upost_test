<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Review
 *
 * @property int $review_id Код
 * @property int $user_id Код пользователя
 * @property int $job_id Код задания
 * @property int $rating Рейтинг
 * @property int $review_type Кто оставил отзыв: заказчик или исполнитель
 * @property string $comment Комментарий
 * @property string $created_at Дата добавления
 * @property string|null $updated_at Дата изменения
 * @method static \Illuminate\Database\Eloquent\Builder|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereReviewId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereReviewType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Review whereUserId($value)
 * @mixin \Eloquent
 */
class Review extends Model
{
    public $timestamps = false;

    public const TYPE_CREATOR = 0;
    public const TYPE_FREELANCER = 1;
    public const TYPES = [
        'creator' => self::TYPE_CREATOR,
        'freelancer' => self::TYPE_FREELANCER,
    ];

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
        $review = static::whereUserId($user_id)
            ->latest()
            ->first(['rating', 'comment', 'created_at']);

        return !empty($review) ? $review : '';
    }
}
