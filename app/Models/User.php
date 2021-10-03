<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;

class User extends Authenticatable
{
    use Notifiable;

    const STATUS_NEW = 'new';
    const STATUS_ACTIVE = 'active';
    const STATUS_BANNED = 'banned';
    const STATUS_REMOVED = 'removed';

    const VALIDATION_STATUS_VALID = 'valid';
    const VALIDATION_STATUS_NO_VALID = 'no_valid';

    const ROLE_USER = 'user';
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_MODERATOR = 'moderator';

    protected $guarded = ['id'];
    public $timestamps = false;

    protected $appends = [
        'photo_thumb',
        'photo_original',
        'favorite_orders_count',
        'favorite_routes_count',
        'register_date_human',
        'last_active_human',
        'age',
        'orders_count',
        'routes_count',
    ];

    /**
     * Список полей пользователя для просмотра.
     *
     * @var array
     */
    const FIELDS_FOR_SHOW = [
        'id',                   # id
        'name',                 # ім’я
        'surname',              # прізвище
        'register_date',        # дата реєстрації
        'last_active',          # час останньої активності
        'status',               # статус
        'birthday',             # день народження
        'gender',               # стать
        'photo',                # фото
        'resume',               # біографія
        'freelancer_rating',    # рейтинг фрілансера
        'creator_rating',       # рейтинг виконавця
        'city_id',              # місто
        'validation',           # статус верифікації даних користувача
    ];

    /**
     * Список полей пользователя для редактирования.
     *
     * @var array
     */
    const FIELDS_FOR_EDIT = [
        'name',                 # ім'я
        'surname',              # прізвище
        'city_id',              # код міста проживання
        'status',               # статус
        'birthday',             # дата народження
        'gender',               # стать
        'photo',                # фото
        'resume',               # біографія
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->register_date = $model->freshTimestamp();
            $model->status = self::STATUS_NEW;
            $model->validation = self::VALIDATION_STATUS_NO_VALID;
            $model->lang = config('user.default.lang');
            $model->currency = config('user.default.currency');
            $model->role = self::ROLE_USER;
        });
    }

    ### SETTERS ###

    public function setCurrencyAttribute($value)
    {
        $this->attributes['currency'] = config('app.currencies')[$value];
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = getHashPassword($value);
    }

    ### GETTERS ###

    public function getGenderAttribute($value): string
    {
        return __("message.user.$value");
    }

    public function getValidationAttribute($value): string
    {
        return __("message.user.$value");
    }

    public function getStatusAttribute($value): string
    {
        return __("message.user.$value");
    }

    public function getAgeAttribute(): string
    {
        return Carbon::parse($this->birthday)->age;
    }

    public function getPhotoAttribute($value): string
    {
        if (is_null($value)) {
            return asset('storage/users/no-photo.png');
        }

        return asset('storage/' . $value);
    }

    public function getPhotoThumbAttribute(): string
    {
        return str_replace('photo.jpg', 'photo-thumb.jpg', $this->photo);
    }

    public function getPhotoOriginalAttribute(): string
    {
        return str_replace('photo.jpg', 'photo-original.jpg', $this->photo);
    }

    /**
     * Поле "На сервисе Х лет/месяцев/дней".
     *
     * @return string
     */
    public function getRegisterDateHumanAttribute(): string
    {
        return Carbon::parse($this->register_date)->diffForHumans(null, true);
    }

    /**
     * Поле "От даты последней активности прошло Х лет/месяцев/дней".
     *
     * @return string
     */
    public function getLastActiveHumanAttribute(): string
    {
        return Carbon::parse($this->last_active)->diffForHumans();
    }

    public function getFavoriteOrdersCountAttribute(): int
    {
        if (is_null($this->favorite_orders)) return 0;

        return substr_count($this->favorite_orders, ',') + 1;
    }

    public function getFavoriteRoutesCountAttribute(): int
    {
        if (is_null($this->favorite_routes)) return 0;

        return substr_count($this->favorite_routes, ',') + 1;
    }

    public function getOrdersCountAttribute(): int
    {
        return $this->orders->count();
    }

    public function getRoutesCountAttribute(): int
    {
        return $this->routes->count();
    }

    ### LINKS ###

    public function city(): HasOne
    {
        $lang = app()->getLocale();

        return $this->hasOne(City::class, 'id', 'city_id')
            ->select(['id', "name_{$lang} as name", 'country_id']);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'user_id', 'id');
    }

    public function ratesDeadlineToday()
    {
        return $this->rates()->deadlineToday();
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function routes(): HasMany
    {
        return $this->hasMany(Route::class, 'user_id', 'id');
    }

    ### SCOPES ###

    public function scopeExclude($query, $value = [])
    {
        return $query->select(array_diff($this->getAttributes(), (array) $value));
    }

    public function scopeExistsToken($query, $token = ''): bool
    {
        if (empty($token)) return false;

        return (bool) $query->where('api_token', $token)->count();
    }
}
