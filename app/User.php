<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    const CREATED_AT = 'user_register_date';
    const UPDATED_AT = null;

    protected $dateFormat = 'Y-m-d';

    protected $fillable = [
        'user_phone',
        'user_email',
        'user_password',
        'user_name',
        'user_surname',
        'user_status',
        'user_validation',
        'user_card_number',
        'user_currency',
        'user_lang',
        'user_role',
    ];
}
