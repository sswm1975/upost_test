<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TimestampSerializable;

class Dispute extends Model
{
    use TimestampSerializable;

    public const STATUS_ACTIVE  = 'active';
    public const STATUS_IN_WORK = 'in_work';
    public const STATUS_CLOSED  = 'closed';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_IN_WORK,
        self::STATUS_CLOSED,
    ];

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = [
        'created_at',
        'updated_at',
        'deadline',
    ];

    ### BOOT ###

    /**
     * Boot model.
     *
     * @return void
     */
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
}
