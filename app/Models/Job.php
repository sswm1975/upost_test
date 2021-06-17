<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $primaryKey = 'job_id';
    protected $fillable = ['rate_id', 'status'];
    public $timestamps = false;
}
