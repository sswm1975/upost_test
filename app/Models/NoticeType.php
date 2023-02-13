<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\NoticeType
 *
 * @property string $id Код
 * @property string $name Наименование
 * @property string|null $mode Режим работы
 * @property string $text_uk Текст уведомления на украинском
 * @property string $text_ru Текст уведомления на русском
 * @property string $text_en Текст уведомления на английском
 * @property int $active Действует (да/нет)
 * @property string|null $description Описание
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @method static Builder|NoticeType newModelQuery()
 * @method static Builder|NoticeType newQuery()
 * @method static Builder|NoticeType query()
 * @method static Builder|NoticeType whereActive($value)
 * @method static Builder|NoticeType whereCreatedAt($value)
 * @method static Builder|NoticeType whereDescription($value)
 * @method static Builder|NoticeType whereId($value)
 * @method static Builder|NoticeType whereMode($value)
 * @method static Builder|NoticeType whereName($value)
 * @method static Builder|NoticeType whereTextEn($value)
 * @method static Builder|NoticeType whereTextRu($value)
 * @method static Builder|NoticeType whereTextUk($value)
 * @method static Builder|NoticeType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NoticeType extends Model
{
    use TimestampSerializable;

    public $incrementing = false;

    const NEW_RATE = 'new_rate';                             # Появилась новая ставка
    const SOON_EXPIRED_ORDER = 'soon_expired_order';         # Скоро крайний срок заказа
    const SELECT_TRAVELER = 'select_traveler';               # Выберите Путешественника
    const PRODUCT_BUYED = 'product_buyed';                   # Товар куплен Путешественником
    const REVIEW_FOR_TRAVELER = 'review_for_traveler';       # Оставьте отзыв для Путешественника
    const REVIEW_FOR_CUSTOMER = 'review_for_customer';       # Оставьте отзыв для Заказчика
    const RATE_ACCEPTED = 'rate_accepted';                   # Ставка принята
    const NEED_BUY_PRODUCT = 'need_buy_product';             # Необходимо купить товар
    const DISPUTE_OPENED = 'dispute_opened';                 # Открыт спор
    const DISPUTE_CLOSED = 'dispute_closed';                 # Закрыт спор
    const PROFILE_NOT_FILLED = 'profile_not_filled';         # Профиль не заполнен
    const EXISTS_NEW_ORDERS = 'exists_new_orders';           # Существуют новые заказы по маршруту
    const SERVICE_NOTICE = 'service_notice';                 # Сервисное уведомление
    const ROUTE_CLOSED = 'route_closed';                     # Закрыт маршрут

    public static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::updateCache();
        });
    }

    public static function initCache()
    {
        $notice_types = Cache::rememberForever('notice_types', function() {
            return NoticeType::all()->keyBy('id')->toArray();
        });

        config(compact('notice_types'));
    }

    public static function updateCache( )
    {
        Cache::forget('notice_types');

        static::initCache();
    }
}

