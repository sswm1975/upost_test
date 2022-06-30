<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Transaction
 *
 * @property int $id Код
 * @property int $user_id Пользователь
 * @property int $rate_id Ставка
 * @property string $type Тип транзакции
 * @property string $amount Сумма
 * @property string $order_amount Сумма заказа
 * @property string $delivery_amount Стоимость доставки
 * @property string $liqpay_fee Комиссия Liqpay
 * @property string $service_fee Комиссия сервиса
 * @property string $export_tax Налог на вывоз товара
 * @property string|null $description Описание
 * @property string|null $status Статус
 * @property array|null $response Ответ от сервиса
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 * @property \Illuminate\Support\Carbon|null $payed_at Дата оплаты
 * @property-read string $status_name
 * @property-read string $type_name
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDeliveryAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereExportTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereLiqpayFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereOrderAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePayedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereServiceFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUserId($value)
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use TimestampSerializable;

    protected $guarded = ['id'];
    protected $casts = ['response' => 'array'];
    protected $dates = ['payed_at'];
    protected $appends = ['status_name', 'type_name'];

    ### GETTERS ###

    public function getStatusNameAttribute(): string
    {
        return $this->status;
    }

    public function getTypeNameAttribute(): string
    {
        return __("message.transaction.types.$this->type");
    }

    ### LINKS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }
}
