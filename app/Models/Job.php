<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Job
 *
 * @property int $id Код
 * @property int $rate_id Код ставки
 * @property string $status Статус
 * @property-read \App\Models\Rate $rate
 * @method static \Illuminate\Database\Eloquent\Builder|Job newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Job newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Job query()
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Job whereStatus($value)
 * @mixin \Eloquent
 */
class Job extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DONE = 'done';
    public const STATUS_WORK = 'work';
    public const STATUS_DISPUTE = 'dispute';
    public const STATUS_SUCCESSFUL = 'successful';

    protected $primaryKey = 'id';
    protected $fillable = ['rate_id', 'status'];
    public $timestamps = false;

    public function rate()
    {
        return $this->belongsTo(Rate::class, 'rate_id')->withDefault();
    }

    public function getUserId()
    {
        return $this->rate->user_id ?? null;
    }
}
