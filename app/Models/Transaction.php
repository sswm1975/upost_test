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
 * @property string $amount Общая сумма
 * @property string $order_amount Сумма заказа
 * @property string $delivery_amount Стоимость доставки
 * @property string $payment_service_fee Комиссия платежной системы
 * @property string $company_fee Комиссия компании
 * @property string $export_tax Налог на вывоз товара
 * @property string|null $description Описание
 * @property string|null $status Статус транзакции
 * @property array|null $purchase_params Параметры для PayPal purchase
 * @property array|null $purchase_response Ответ от PayPal purchase
 * @property string|null $purchase_redirect_url Ссылка для оплаты в PayPal
 * @property array|null $complete_response Ответ от сервиса PayPal
 * @property array|null $complete_error Ошибка при complete (статус not_successful)
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 * @property \Illuminate\Support\Carbon|null $payed_at Дата оплаты
 * @property string|null $stripe_checkout_session_id Идентификатор сеанса оплаты в платежной системе Stripe
 * @property string|null $stripe_payment_intent_id Идентификатор платежного намерения в платежной системе Stripe (используется при Refund)
 * @property-read string $status_name
 * @property-read string $type_name
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCompanyFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCompleteError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCompleteResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDeliveryAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereExportTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereOrderAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePayedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePaymentServiceFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePurchaseParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePurchaseRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePurchaseResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStripeCheckoutSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStripePaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUserId($value)
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use TimestampSerializable;

    protected $guarded = ['id'];
    protected $casts = [
        'response' => 'array',
        'purchase_params' => 'array',
        'purchase_response' => 'array',
        'complete_response' => 'array',
        'complete_error' => 'array',
    ];
    protected $dates = ['payed_at'];
    protected $appends = ['status_name', 'type_name'];
    protected $attributes = ['type' => self::TYPE_PAYMENT];

    public const TYPE_PAYMENT = 'payment';

    ### GETTERS ###

    public function getStatusNameAttribute(): string
    {
        return __("message.transaction.statuses.$this->status");
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
