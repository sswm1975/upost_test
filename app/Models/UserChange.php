<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserChange extends Model
{
    use SoftDeletes;

    protected $table = 'users_change';
    protected $primaryKey = 'token';
}
