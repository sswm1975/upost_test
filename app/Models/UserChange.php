<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * App\Models\UserChange
 *
 * @property int $users_change_id Код
 * @property string $token Токен
 * @property int $user_id Код пользователя
 * @property string|null $user_email Емейл
 * @property string|null $user_phone Телефон
 * @property string|null $user_password Пароль
 * @property string|null $user_card_number Номер банковской карточки
 * @property string|null $user_card_name Наименование банковской карточки
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата редактирования
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата удаления
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange newQuery()
 * @method static \Illuminate\Database\Query\Builder|UserChange onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUserCardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUserCardNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUserEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUserPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUserPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUsersChangeId($value)
 * @method static \Illuminate\Database\Query\Builder|UserChange withTrashed()
 * @method static \Illuminate\Database\Query\Builder|UserChange withoutTrashed()
 * @mixin \Eloquent
 */
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
