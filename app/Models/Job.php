<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    public const STATUS_DONE = 'done';

    protected $primaryKey = 'job_id';
    protected $fillable = ['rate_id', 'rate_status'];
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
