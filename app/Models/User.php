<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;

class User extends Authenticatable
{
    use Notifiable;

    const STATUS_ACTIVE = 'active';
    const STATUS_BANNED = 'banned';
    const STATUS_REMOVED = 'removed';

    const VALIDATION_STATUS_VALID = 'valid';
    const VALIDATION_STATUS_NO_VALID = 'no_valid';

    const ROLE_USER = 'user';
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_MODERATOR = 'moderator';

    protected $primaryKey = 'user_id';
    protected $guarded = ['user_id'];
    public $timestamps = false;

    /**
     * Список полей пользователя для просмотра.
     *
     * @var array
     */
    const FIELDS_FOR_SHOW = [
        'user_id',                   # id
        'user_name',                 # ім’я
        'user_surname',              # прізвище
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
            $model->user_status = self::STATUS_ACTIVE;
            $model->user_validation = self::VALIDATION_STATUS_NO_VALID;
            $model->user_lang = config('user.default.lang');
            $model->user_currency = config('user.default.currency');
            $model->user_role = self::ROLE_USER;
        });
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'user_id', 'user_id');
    }

    public function ratesDeadlineToday()
    {
        return $this->rates()->deadlineToday();
    }

    public function setUserCurrencyAttribute($value)
    {
        $this->attributes['user_currency'] = config('app.currencies')[$value];
    }

    public function scopeExclude($query, $value = [])
    {
        return $query->select(array_diff($this->getAttributes(), (array) $value));
    }

    public function scopeExistsToken($query, $token = '')
    {
        if (empty($token)) return false;

        return (bool) $query->where('api_token', $token)->count();
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  Notification  $notification
     * @return string
     */
    public function routeNotificationForMail(Notification $notification): string
    {
        return $this->user_email;
    }

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->user_email;
    }
}
