<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StripeLog
 *
 * @property int $id Код
 * @property int $user_id Користувач
 * @property string $method API-метод
 * @property array|null $in_params Вхідны параметри
 * @property array|null $response Відповідь від Stripe
 * @property int $is_error Це помилка?
 * @property \Illuminate\Support\Carbon|null $created_at Створено
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog whereInParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog whereIsError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StripeLog whereUserId($value)
 * @mixin \Eloquent
 */
class StripeLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['created_at'];
    protected $casts = ['in_params' => 'array', 'response' => 'array'];

    /**
     * В лог добавить запись.
     *
     * @param $method
     * @param $in_params
     * @param $response
     * @param false $is_error
     */
    public static function add($method, $in_params, $response, $is_error = false)
    {
        $user_id = request()->has('dispute_user_id') ? request('dispute_user_id') : request()->user()->id ?? 0;

        static::create([
            'user_id'    => $user_id,
            'method'     => $method,
            'in_params'  => $in_params,
            'response'   => $response,
            'is_error'   => $is_error,
            'created_at' => Carbon::now()->toDateTimeString(),
        ]);
    }
}
