<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
