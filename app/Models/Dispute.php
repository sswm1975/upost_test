<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
