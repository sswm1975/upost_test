<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Transaction
 *
 * @property int $id Код
 * @property int $user_id Пользователь
 * @property int $rate_id Ставка
 * @property string $amount Сумма
 * @property string|null $description Описание
 * @property string|null $status Статус
 * @property array|null $response Ответ от сервиса
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 * @property \Illuminate\Support\Carbon|null $payed_at Дата оплаты
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePayedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUserId($value)
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['response' => 'array'];
    protected $dates = ['payed_at'];
}
