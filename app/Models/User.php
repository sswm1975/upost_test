<?php

namespace App\Models;

use App\Models\Traits\WithoutAppends;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Date;
use App\Models\Traits\TimestampSerializable;

/**
 * App\Models\User
 *
 * @property int $id Код
 * @property string|null $phone Телефон
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
 * @property string|null $role Роль
 * @property string|null $photo Ссылка на фотографию (аватар)
 * @property string|null $resume Биография/Резюме
 * @property string $wallet Баланс в долларах
 * @property int $scores_count Количество баллов
 * @property int $reviews_count Количество отзывов
 * @property int $failed_delivery_count Количество неудачных доставок
 * @property int $failed_receive_count Количество неудачных получений
 * @property string|null $api_token Токен для работы через API
 * @property string|null $google_id ID пользователя Google
 * @property string|null $facebook_id ID пользователя Facebook
 * @property string|null $register_date Дата регистрации
 * @property \Illuminate\Support\Carbon|null $last_active Дата и время последней активности
 * @property \Illuminate\Support\Carbon $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read \App\Models\City|null $city
 * @property-read string $age
 * @property-read string $full_name
 * @property-read string $gender_name
 * @property-read string $last_active_human
 * @property-read string $photo_original
 * @property-read string $photo_thumb
 * @property-read string $rating
 * @property-read string $register_date_human
 * @property-read string $short_name
 * @property-read string $status_name
 * @property-read string $validation_name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rate[] $rates
 * @property-read int|null $rates_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Route[] $routes
 * @property-read int|null $routes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $successful_orders
 * @property-read int|null $successful_orders_count
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
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFacebookId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFailedDeliveryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFailedReceiveCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGoogleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRegisterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereResume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereReviewsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereScoresCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSurname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereValidation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereWallet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutAppends(array $appends = [])
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use TimestampSerializable;
    use Notifiable;
    use WithoutAppends;

    protected $guarded = ['id'];
    protected $dates = ['last_active'];
    protected $appends = [
        'short_name',
        'full_name',
        'status_name',
        'gender_name',
        'validation_name',
        'photo_thumb',
        'photo_original',
        'register_date_human',
        'last_active_human',
        'age',
        'rating',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_NOT_ACTIVE = 'not_active';
    const STATUS_BANNED = 'banned';
    const STATUS_REMOVED = 'removed';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_NOT_ACTIVE,
        self::STATUS_BANNED,
        self::STATUS_REMOVED,
    ];

    const VALIDATION_STATUS_VALID = 'valid';
    const VALIDATION_STATUS_NO_VALID = 'no_valid';

    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_UNKNOWN = 'unknown';

    const GENDERS = [
        self::GENDER_MALE,
        self::GENDER_FEMALE,
        self::GENDER_UNKNOWN,
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
        'scores_count',         # количество баллов
        'reviews_count',        # количество отзывов
        'failed_delivery_count',# количество неудачных доставок
        'failed_receive_count', # количество неудачных получений
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
            $model->register_date = Date::now()->format('Y-m-d');
            $model->lang = $model->lang ?? config('user.default.lang');
            $model->currency = $model->currency ?? config('user.default.currency');
        });
    }

    ### SETTERS ###

    public function setPhotoAttribute($photo)
    {
        if (empty($photo)) {
            $this->attributes['photo'] = null;
        }

        $uri_parts = explode('/', $photo);

        $this->attributes['photo'] = end($uri_parts);
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = getHashPassword($value);
    }

    ### GETTERS ###

    public function getShortNameAttribute(): string
    {
        return $this->name . ($this->surname ? ' ' . mb_substr($this->surname, 0, 1) . '.' : '');
    }

    public function getFullNameAttribute(): string
    {
        return $this->name . ($this->surname ? ' ' . $this->surname : '');
    }

    public function getGenderNameAttribute(): string
    {
        return __("message.user.genders.$this->gender");
    }

    public function getValidationNameAttribute(): string
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

    public function getRatingAttribute(): string
    {
        return round($this->reviews_count ? $this->scores_count / $this->reviews_count : 0, 2);
    }

    public function getPhotoAttribute(): string
    {
        if (empty($this->photo)) {
            return asset('storage/user_no_photo.png');
        }

        return asset("storage/{$this->id}/user/{$this->photo}");
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
    public function successful_orders(): HasMany
    {
        return $this->orders()->whereStatus(Order::STATUS_SUCCESSFUL);
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
