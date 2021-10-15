<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;

/**
 * App\Models\User
 *
 * @property int $id Код
 * @property string $phone Телефон
 * @property string $email Емейл
 * @property string $password Пароль
 * @property string|null $name Имя пользователя
 * @property string|null $surname Фамилия пользователя
 * @property int|null $city_id Код города
 * @property string $status Статус
 * @property string|null $card_number Номер банковской карты
 * @property string|null $card_name Наименование банковской карты
 * @property string|null $birthday Дата рождения
 * @property string $gender Пол
 * @property string|null $lang Язык для системы
 * @property string|null $currency Валюта
 * @property string|null $validation Признак проверки пользователя
 * @property string $register_date Дата регистрации
 * @property string|null $role Роль
 * @property string $photo Ссылка на фотографию (аватар)
 * @property string|null $favorite_orders Список избранных заказов
 * @property string|null $favorite_routes Список избранных маршрутов
 * @property string|null $last_active Дата и время последней активности
 * @property string|null $resume Биография/Резюме
 * @property string $wallet Баланс в долларах
 * @property int $creator_rating Рейтинг заказчика
 * @property int $freelancer_rating Рейтинг исполнителя
 * @property string|null $api_token Токен для работы через API
 * @property-read \App\Models\City|null $city
 * @property-read string $age
 * @property-read int $favorite_orders_count
 * @property-read int $favorite_routes_count
 * @property-read string $gender_name
 * @property-read string $last_active_human
 * @property-read int|null $orders_count
 * @property-read string $photo_original
 * @property-read string $photo_thumb
 * @property-read string $register_date_human
 * @property-read int|null $routes_count
 * @property-read string $status_name
 * @property-read string $validation_name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rate[] $rates
 * @property-read int|null $rates_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Route[] $routes
 * @method static \Illuminate\Database\Eloquent\Builder|User exclude($value = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User existsToken($token = '')
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCardNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatorRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFavoriteOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFavoriteRoutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFreelancerRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRegisterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereResume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSurname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereValidation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereWallet($value)
 * @mixin \Eloquent
 */
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
        'status_name',
        'gender_name',
        'validation_name',
        'photo_thumb',
        'photo_original',
        'favorite_orders_count',
        'favorite_routes_count',
        'register_date_human',
        'last_active_human',
        'age',
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

    public function getGenderNameAttribute(): string
    {
        return __("message.user.genders.$this->gender");
    }

    public function getValidationNameAttribute($value): string
    {
        return __("message.user.validations.$this->validation");
    }

    public function getStatusNameAttribute(): string
    {
        return __("message.user.statuses.$this->status");
    }

    public function getAgeAttribute(): string
    {
        return Carbon::parse($this->birthday)->age;
    }

    public function getPhotoAttribute($photo): string
    {
        if (is_null($photo)) {
            return asset('storage/user_no_photo.png');
        }

        return asset("storage/{$this->id}/user/{$photo}");
    }

    public function getPhotoThumbAttribute(): string
    {
        return str_replace('image_', 'image_thumb_', $this->photo);
    }

    public function getPhotoOriginalAttribute(): string
    {
        return str_replace('image_', 'image_original_', $this->photo);
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
