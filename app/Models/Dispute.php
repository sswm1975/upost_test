<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Dispute
 *
 * @property int $dispute_id Код
 * @property int $user_id Код пользователя
 * @property int $job_id Код задания
 * @property int $problem_id Код проблемы
 * @property array $files Файлы
 * @property string $comment Комментарий
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата изменения
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute query()
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereDisputeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereProblemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dispute whereUserId($value)
 * @mixin \Eloquent
 */
class Dispute extends Model
{
    protected $primaryKey = 'dispute_id';

    protected $fillable = [
        'user_id',
        'job_id',
        'problem_id',
        'files',
        'comment',
    ];

    protected $casts = [
        'files' => 'array',
    ];


    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->user_id;
        });
    }
}
