<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Withdrawal
 *
 * @property int $id Код
 * @property int $user_id Користувач
 * @property string $amount Сума
 * @property string $email Електрона адреса
 * @property string $status Статус
 * @property int|null $file_id CSV-файл, в якому відправлена заявка на вивід грошей
 * @property \Illuminate\Support\Carbon|null $created_at Створено
 * @property \Illuminate\Support\Carbon|null $updated_at Змінено
 * @property-read \App\Models\WithdrawalFile|null $file
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal existsUnfinished()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal owner()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal query()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdrawal whereUserId($value)
 * @mixin \Eloquent
 */
class Withdrawal extends Model
{
    use TimestampSerializable;

    const STATUS_NEW = 'new';
    const STATUS_DONE = 'done';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_FAIL = 'fail';
    const STATUS_EXPIRED = 'expired';

    const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_IN_PROGRESS,
        self::STATUS_DONE,
        self::STATUS_EXPIRED,
        self::STATUS_FAIL,
    ];

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at',];

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
            $user = request()->user();
            $model->user_id = $user->id;
            $model->amount = $user->wallet;
            $model->email = $user->email;
            $model->status = self::STATUS_NEW;
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
    }

    ### RELATIONS ###

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(WithdrawalFile::class);
    }

    ### SCOPES ###

    public function scopeOwner($query)
    {
        return $query->where('user_id', request()->user()->id);
    }

    public function scopeExistsUnfinished($query)
    {
        return $query->owner()
            ->whereIn('status', [Withdrawal::STATUS_NEW, Withdrawal::STATUS_IN_PROGRESS])
            ->exists();
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', Withdrawal::STATUS_IN_PROGRESS);
    }
}
