<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;

/**
 * App\Models\User
 *
 * @property int $user_id Код
 * @property string $user_phone Телефон
 * @property string $user_email Емейл
 * @property string $user_password Пароль
 * @property string|null $user_name Имя пользователя
 * @property string|null $user_surname Фамилия пользователя
 * @property int|null $user_city Код города
 * @property string $user_status Статус
 * @property string|null $user_card_number Номер банковской карты
 * @property string|null $user_card_name Наименование банковской карты
 * @property string|null $user_birthday Дата рождения
 * @property string|null $user_gender Пол
 * @property string|null $user_lang Язык для системы
 * @property string|null $user_currency Валюта
 * @property string|null $user_validation Признак проверки пользователя
 * @property string|null $user_register_date Дата регистрации
 * @property string|null $user_role Роль
 * @property string|null $user_photo Ссылка на фотографию (аватар)
 * @property string|null $user_favorite_orders Список избранных заказов
 * @property string|null $user_favorite_routes Список избранных маршрутов
 * @property string|null $user_last_active Дата и время последней активности
 * @property string|null $user_resume Биография/Резюме
 * @property string $user_wallet Баланс в долларах
 * @property int $user_creator_rating Рейтинг заказчика
 * @property int $user_freelancer_rating Рейтинг исполнителя
 * @property string|null $api_token Токен для работы через API
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rate[] $rates
 * @property-read int|null $rates_count
 * @method static \Illuminate\Database\Eloquent\Builder|User exclude($value = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User existsToken($token = '')
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserCardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserCardNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserCreatorRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserFavoriteOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserFavoriteRoutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserFreelancerRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserLastActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserRegisterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserResume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserSurname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserValidation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserWallet($value)
 * @mixin \Eloquent
 */
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
    protected $appends = [
        'user_photo_thumb',
        'user_photo_original',
        'user_favorite_orders_count',
        'user_favorite_routes_count',
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

    ### SETTERS ###

    public function setUserCurrencyAttribute($value)
    {
        $this->attributes['user_currency'] = config('app.currencies')[$value];
    }

    ### GETTERS ###

    public function getUserPhotoAttribute($value): string
    {
        if (is_null($value)) {
            return asset('storage/users/no-photo.png');
        }

        return asset('storage/' . $value);
    }

    public function getUserPhotoThumbAttribute(): string
    {
        return str_replace('user_photo.jpg', 'user_photo-thumb.jpg', $this->user_photo);
    }

    public function getUserPhotoOriginalAttribute(): string
    {
        return str_replace('user_photo.jpg', 'user_photo-original.jpg', $this->user_photo);
    }

    public function getUserFavoriteOrdersCountAttribute(): int
    {
        if (is_null($this->user_favorite_orders)) return 0;

        return substr_count($this->user_favorite_orders, ',') + 1;
    }

    public function getUserFavoriteRoutesCountAttribute(): int
    {
        if (is_null($this->user_favorite_routes)) return 0;

        return substr_count($this->user_favorite_routes, ',') + 1;
    }

    ### LINKS ###

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'user_id', 'user_id');
    }

    public function ratesDeadlineToday()
    {
        return $this->rates()->deadlineToday();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    ### SCOPES ###

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
