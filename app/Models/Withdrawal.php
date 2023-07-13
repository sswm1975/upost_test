<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use TimestampSerializable;

    const STATUS_NEW = 'new';
    const STATUS_DONE = 'done';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_FAIL = 'fail';
    const STATUS_EXPIRED = 'expired';

    const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_IN_PROGRESS,
        self::STATUS_DONE,
        self::STATUS_EXPIRED,
        self::STATUS_FAIL,
    ];

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at',];

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
            $user = request()->user();
            $model->user_id = $user->id;
            $model->amount = $user->wallet;
            $model->email = $user->email;
            $model->status = self::STATUS_NEW;
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
    }

    ### RELATIONS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    ### SCOPES ###

    public function scopeOwner($query)
    {
        return $query->where('user_id', request()->user()->id);
    }

    public function scopeExistsUnfinished($query)
    {
        return $query->owner()
            ->whereIn('status', [Withdrawal::STATUS_NEW, Withdrawal::STATUS_IN_PROGRESS])
            ->exists();
    }
}
