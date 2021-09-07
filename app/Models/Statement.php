<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Statement
 *
 * @property int $id Код
 * @property int $user_id Пользователь
 * @property int $rate_id Ставка
 * @property string $status Статус
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 * @method static \Illuminate\Database\Eloquent\Builder|Statement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Statement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Statement query()
 * @method static \Illuminate\Database\Eloquent\Builder|Statement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Statement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Statement whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Statement whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Statement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Statement whereUserId($value)
 * @mixin \Eloquent
 */
class Statement extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DONE = 'done';

    protected $fillable = ['user_id', 'rate_id', 'status'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->user_id;
            $model->status = self::STATUS_ACTIVE;
        });
    }
}
