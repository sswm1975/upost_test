<?php

namespace App\Modules;

use App\Models\Order;
use App\Models\OrderDeduction;
use App\Models\Tax;
use Carbon\Carbon;
use ParseError;

/**
 * Класс "Расчёт комиссий и налогов по заказу".
 */
class Calculations
{
    /**
     * Список комиссий.
     *
     * @var array
     */
    const FEES = ['liqpay_percent', 'service_fee_percent'];

    /**
     * Выполнить расчет комиссий и налогов по заказу.
     *
     * @param Order $order
     * @param bool $recalculation
     * @return void
     * @throws \Exception
     */
    public static function run(Order $order, bool $recalculation = false)
    {
        # рассчитываем суммы комиссий
        $fees = static::calcFees($order->price_usd, $order->id);

        # рассчитываем налоги по стране экспорта
        $export = static::calcTaxes('export', $order->from_country_id, $order->price_usd, $order->id);

        # рассчитываем налоги по стране импорта
        $import = static::calcTaxes('import', $order->to_country_id, $order->price_usd, $order->id);

        # объединяем все расчеты
        $calculations = array_merge($fees, $export, $import);

        # если установлен флаг пересчета, то удаляем предыдущие расчеты
        if ($recalculation) {
            OrderDeduction::whereOrderId($order->id)->delete();
        }

        # расчеты заносим в таблицу
        OrderDeduction::insert($calculations);
    }

    /**
     * Рассчитать комиссии по заказу.
     *
     * @param float $amount  Сумма в долларах
     * @param int $order_id  Код заказа
     * @return array
     */
    protected static function calcFees(float $amount, int $order_id): array
    {
        $calculations = [];
        foreach (self::FEES as $fee) {
            $calculations[] = [
                'order_id'   => $order_id,
                'type'       => 'fee',
                'name'       => $fee,
                'amount'     => $amount * config($fee, 0) / 100,
                'created_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        return $calculations;
    }

    /**
     * Рассчитать налоги по заказу.
     *
     * @param string $type     Тип налога: export / import
     * @param int $country_id  Страна
     * @param float $amount    Сумма в долларах
     * @param int $order_id    Код заказа
     * @return array
     */
    protected static function calcTaxes(string $type, int $country_id, float $amount, int $order_id): array
    {
        # узнаем список налогов по коду страны
        $taxes = Tax::$type($country_id)->toArray();

        if (empty($taxes)) {
            return [];
        }

        $calculations = [];
        foreach ($taxes as $alias => $code) {
            $tax_amount = static::runScript($code, $amount);
            $calculations[] = [
                'order_id'   => $order_id,
                'type'       => 'tax_' . $type,
                'name'       => $alias,
                'amount'     => $tax_amount,
                'created_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        return $calculations;
    }

    /**
     * Выполнить PHP-скрипт.
     *
     * @param string    $code    PHP-код
     * @param float|int $amount  Сумма в долларах
     * @return int
     */
    public static function runScript(string $code = '', float $amount = 0): int
    {
        if (empty($code) || empty($amount)) {
            return 0;
        }

        $code = str_replace(
            ['<?php', '?>', '{ORDER_SUMMA_USD}'],
            ['', '', $amount],
            $code
        );

        try {
            return eval($code);
        } catch (ParseError $err) {
            return 0;
        }
    }
}
