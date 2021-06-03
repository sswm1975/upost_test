<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = 'user_id';
    protected $guarded = ['user_id'];
    public $timestamps = false;

    protected $columns = [
        'user_id',
        'user_phone',
        'user_email',
        'user_password',
        'user_hash',
        'user_ip',
        'user_name',
        'user_surname',
        'user_rating',
        'user_city',
        'user_location',
        'user_status',
        'user_card_number',
        'user_card_name',
        'user_birthday',
        'user_gender',
        'user_lang',
        'user_currency',
        'user_validation',
        'user_register_date',
        'user_role',
        'user_photo',
        'user_favorite_orders',
        'user_favorite_routes',
        'user_last_active',
        'user_resume',
        'user_messages_viewed',
        'user_wallet',
        'user_creater_rating',
        'user_freelancer_rating',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_register_date = $model->freshTimestamp();
        });
    }

    public function scopeExclude($query, $value = [])
    {
        return $query->select(array_diff($this->columns, (array) $value));
    }
}
