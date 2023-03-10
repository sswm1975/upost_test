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
     * Выполнить расчет комиссий и налогов по заказу.
     *
     * @param Order $order           Заказ
     * @return void
     * @throws \Exception
     */
    public static function run(Order $order)
    {
        # рассчитываем суммы комиссий
        $fees = static::calcFees($order->total_amount_usd, $order->id);

        # рассчитываем налоги по стране экспорта
        $export = static::calcTaxes('export', $order->from_country_id, $order->total_amount_usd, $order->id);

        # рассчитываем налоги по стране импорта
        $import = static::calcTaxes('import', $order->to_country_id, $order->total_amount_usd, $order->id);

        # объединяем все расчеты
        $calculations = array_merge($fees, $export, $import);

        # удаляем предыдущие расчеты
        OrderDeduction::whereOrderId($order->id)->delete();

        # расчеты заносим в таблицу
        OrderDeduction::insert($calculations);

        # сохраняем общую сумму налогов и комиссий в заказе (выполняем без вызова событий)
        $order->withoutEvents(function () use ($order, $calculations) {
            $order->deduction_usd = collect($calculations)->sum('amount');
            $order->save();
        });
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
        # получаем список комиссий из константы fees
        $fees = array_map('trim', explode(',', config('fees')));

        $calculations = [];
        foreach ($fees as $fee) {
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
     * @param string $type        Тип налога: export / import
     * @param string $country_id  Страна
     * @param float $amount       Сумма в долларах
     * @param int $order_id       Код заказа
     * @return array
     */
    protected static function calcTaxes(string $type, string $country_id, float $amount, int $order_id): array
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
