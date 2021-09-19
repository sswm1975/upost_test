<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * App\Models\UserChange
 *
 * @property int $id Код
 * @property string $token Токен
 * @property int $user_id Код пользователя
 * @property string|null $email Емейл
 * @property string|null $phone Телефон
 * @property string|null $password Пароль
 * @property string|null $card_number Номер банковской карточки
 * @property string|null $card_name Наименование банковской карточки
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата редактирования
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата удаления
 * @property-write mixed $user_password
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange newQuery()
 * @method static \Illuminate\Database\Query\Builder|UserChange onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereCardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereCardNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChange whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|UserChange withTrashed()
 * @method static \Illuminate\Database\Query\Builder|UserChange withoutTrashed()
 * @mixin \Eloquent
 */
class UserChange extends Model
{
    use SoftDeletes;

    protected $table = 'user_changes';
    protected $primaryKey = 'id';
    protected $fillable = [
        'email',
        'phone',
        'password',
        'card_number',
        'card_name',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->token = static::generateToken();
            $model->user_id = request()->user()->id;
        });
    }

    public function setUserPasswordAttribute($value)
    {
        $this->attributes['password'] = getHashPassword($value);
    }

    /**
     * Generate the verification token.
     *
     * @return string
     */
    private static function generateToken(): string
    {
        return Str::random(8);
    }
}
