<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ServiceNotice
 *
 * @property int $id Код
 * @property string $name Наименование
 * @property string $text_uk Текст уведомления на украинском
 * @property string $text_ru Текст уведомления на русском
 * @property string $text_en Текст уведомления на английском
 * @property int|null $admin_user_id Администратор, который отправил уведомление
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property \Illuminate\Support\Carbon|null $sent_at Отправлено
 * @property-read \App\Models\Notice $notices
 * @property-read Administrator|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereAdminUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereTextEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereTextRu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereTextUk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceNotice whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ServiceNotice extends Model
{
    use TimestampSerializable;

    protected $dates = ['sent_at'];

    /**
     * Администратор (менеджер), который отправил системное уведомление.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Administrator::class, 'id', 'admin_user_id');
    }

    /**
     * Список отправленных сервисных уведомлений.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function notices(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Notice::class, 'id', 'object_id')
            ->where('notice_type', '=', NoticeType::SERVICE_NOTICE);
    }
}
