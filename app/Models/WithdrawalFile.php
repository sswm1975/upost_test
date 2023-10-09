<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\WithdrawalFile
 *
 * @property int $id Код
 * @property string|null $name Найменування файлу
 * @property int|null $count Кількість записів
 * @property int|null $summa Загальна сумма (в доларах)
 * @property string|null $payout_id Ідентифікатор платежу в платежній системі Stripe
 * @property mixed|null $payout_response Відповідь АПІ-визову stripe->payouts->create
 * @property \Illuminate\Support\Carbon $created_at Створено
 * @property-read \App\Models\Withdrawal|null $withdrawal
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile query()
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile wherePayoutId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile wherePayoutResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithdrawalFile whereSumma($value)
 * @mixin \Eloquent
 */
class WithdrawalFile extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['created_at'];
    protected $casts = ['payout_response' => 'array'];

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
    }

    ### RELATIONS ###

    public function withdrawal(): HasOne
    {
        return $this->hasOne(Withdrawal::class, 'file_id','id')
            ->latest('id')
            ->limit(1);
    }

}
