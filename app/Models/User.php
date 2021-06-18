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
        'user_creator_rating',
        'user_freelancer_rating',
    ];

    /**
     * Список полей пользователя для просмотра.
     *
     * @var array
     */
    const FIELDS_FOR_SHOW = [
        'user_id',                   # id
        'user_name',                 # ім’я
        'user_surname',              # прізвище
        'user_location',             # локація
        'user_register_date',        # дата реєстрації
        'user_last_active',          # час останньої активності
        'user_status',               # статус
        'user_birthday',             # день народження
        'user_gender',               # стать
        'user_photo',                # фото
        'user_resume',               # біографія
        'user_freelancer_rating',    # рейтинг фрілансера
        'user_creator_rating',       # рейтинг виконавця
    ];

    /**
     * Список полей пользователя для редактирования.
     *
     * @var array
     */
    const FIELDS_FOR_EDIT = [
        'user_name',                 # ім'я
        'user_surname',              # прізвище
        'user_city',                 # код міста проживання
        'user_location',             # код міста перебування
        'user_status',               # статус
        'user_birthday',             # дата народження
        'user_gender',               # стать
        'user_photo',                # фото
        'user_resume',               # біографія
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_register_date = $model->freshTimestamp();
        });
    }

    public function setUserCurrencyAttribute($value)
    {
        $this->attributes['user_currency'] = config('app.currencies')[$value];
    }

    public function scopeExclude($query, $value = [])
    {
        return $query->select(array_diff($this->columns, (array) $value));
    }
}
