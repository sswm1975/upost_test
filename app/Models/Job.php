<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Job
 *
 * @property int $job_id Код
 * @property int $rate_id Код ставки
 * @property string $job_status Статус
 * @property-read \App\Models\Rate $rate
 * @method static \Illuminate\Database\Eloquent\Builder|Job newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Job newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Job query()
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereJobStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereRateId($value)
 * @mixin \Eloquent
 */
class Job extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DONE = 'done';
    public const STATUS_WORK = 'work';
    public const STATUS_DISPUTE = 'dispute';
    public const STATUS_SUCCESSFUL = 'successful';

    protected $primaryKey = 'job_id';
    protected $fillable = ['rate_id', 'job_status'];
    public $timestamps = false;

    public function rate()
    {
        return $this->belongsTo(Rate::class, 'rate_id');
    }

    public function getUserId()
    {
        return $this->rate->user_id ?? null;
    }
}
