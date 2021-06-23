<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    public const STATUS_DONE = 'done';
    public const STATUS_WORK = 'work';
    public const STATUS_DISPUTE = 'dispute';

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
