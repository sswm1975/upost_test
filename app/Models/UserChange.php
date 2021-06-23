<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UserChange extends Model
{
    use SoftDeletes;

    protected $table = 'users_change';
    protected $primaryKey = 'users_change_id';
    protected $fillable = [
        'token',
        'user_id',
        'user_email',
        'user_phone',
        'user_password',
        'user_card_number',
        'user_card_name',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->token = static::generateToken();
            $model->user_id = request()->user()->user_id;
        });
    }

    public function setUserPasswordAttribute($value)
    {
        $this->attributes['user_password'] = getHashPassword($value);
    }

    /**
     * Generate the verification token.
     *
     * @return string|bool
     */
    public static function generateToken()
    {
        return Str::random(8);
    }
}
