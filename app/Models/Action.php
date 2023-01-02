<?php

namespace App\Models;

use App\Events\ActionEvent;
use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Action
 *
 * @property int $id Код
 * @property int $user_id Код пользователя (инициатора или участника действия)
 * @property int $is_owner Пользователь является инициатором действия?
 * @property string $name Наименование действия/события
 * @property array|null $changed Изменения
 * @property array|null $data Данные
 * @property \Illuminate\Support\Carbon $created_at Добавлено
 * @method static \Illuminate\Database\Eloquent\Builder|Action newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Action newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Action query()
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereChanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereIsOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereUserId($value)
 * @mixin \Eloquent
 */
class Action extends Model
{
    use TimestampSerializable;

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['created_at'];
    protected $casts = ['changed' => 'array', 'data' => 'array'];

    # User's actions
    const USER_LOGIN            = 'user_login';
    const USER_LOGOUT           = 'user_logout';
    const USER_REGISTER         = 'user_register';
    const USER_DELETED          = 'user_deleted';
    const USER_RESTORED         = 'user_restored';
    const USER_CARD_UPDATED     = 'user_card_updated';
    const USER_AUTH_UPDATED     = 'user_auth_updated';
    const USER_PROFILE_UPDATED  = 'user_profile_updated';


    # Order's actions
    const ORDER_CREATED         = 'order_created';
    const ORDER_UPDATES         = 'order_updated';
    const ORDER_DELETED         = 'order_deleted';
    const ORDER_RESTORED        = 'order_restored';
    const ORDER_BANNED          = 'order_banned';
    const ORDER_STATUS_CHANGED  = 'order_status_changed';
    const ORDER_STRIKE_CHANGED  = 'order_strike_changed';
    const ORDER_LOOKS_CHANGED   = 'order_looks_changed';

    ### BOOT ###

    /**
     * Boot model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });

        static::created(function ($model) {
            try {
                broadcast(new ActionEvent($model));
            } catch (\Exception $e) {
                \Log::error('Action broadcast error');
                \Log::error($e->getMessage());
            }
        });
    }
}
