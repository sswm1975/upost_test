<?php

namespace App\Modules;

class Liqpay
{
    /**
     * Сформировать параметры для Liqpay-платежа.
     *
     * @param int $user_id
     * @param string $user_name
     * @param int $rate_id
     * @param float $amount
     * @param string $currency
     * @param string $description
     * @param string $language
     * @return array
     */
    public static function create_params(int $user_id, string $user_name, int $rate_id, float $amount, string $currency, string $description = '', string $language = ''): array
    {
        $public_key = config('app.liqpay_public_key');
        $private_key = config('app.liqpay_private_key');

        $language = $language ?: app()->getLocale();

        $info = self::encode_params([
            'user_id' => $user_id,
            'rate_id' => $rate_id,
        ]);

        # см. описание https://www.liqpay.ua/documentation/api/aquiring/checkout/doc
        $params = [
            'public_key'       => $public_key,                       # Публичный ключ.
            'version'          => 3,                                 # Версия API (Number).
            'action'           => 'pay',                             # Тип операции. Возможные значения: pay - платеж, hold - блокировка средств на счету отправителя, subscribe - регулярный платеж, paydonate - пожертвование, auth - предавторизация карты.
            'amount'           => $amount,                           # Сумма платежа.Например: 5, 7.34
            'currency'         => $currency,                         # Валюта платежа. Возможные значения: USD, EUR, RUB, UAH.
            'description'      => $description,                      # Назначение платежа.
            'sender_last_name' => $user_name,                        # Фамилия отправителя.
            'info'             => $info,                             # Информация для добавления данных к платежу.
            'order_id'         => self::create_order_id(),           # Уникальный ID покупки в Вашем магазине. Максимальная длина 255 символов.
            'language'         => $language,                         # Язык клиента ru, uk, en.
            'paytypes'         => 'card',                            # Указывается способ оплаты, которые будут отображены на чекауте. Возможные значения card - оплата картой, liqpay - через кабинет liqpay, privat24 - через кабинет приват24, masterpass - через кабинет masterpass, moment_part - рассрочка, cash - наличными, invoice - счет на e-mail, qr - сканирование qr-кода. Если параметр не передан, то применяются настройки магазина, вкладка Checkout.
            'result_url'       => env('WORDPRESS_URL'),          # URL в Вашем магазине, на который покупатель будет переадресован после завершения покупки.
            'server_url'       => env('WORDPRESS_URL'),          # URL API в Вашем магазине для уведомлений об изменении статуса платежа (сервер->сервер).
            'sandbox'          => '1',                               # Включает тестовый режим: 1-Да, 0-Нет. При тестовом режиме средства с карты плательщика не списываются.
        ];

        \Log::info($params);

        $data = self::encode_params($params);
        $signature = self::str_to_sign($private_key . $data . $private_key);

        return compact('data', 'signature', 'amount', 'currency');
    }

    /**
     * Create order id.
     *
     * @return string
     */
    private static function create_order_id(): string
    {
        $micro = sprintf("%06d", (microtime(true) - floor(microtime(true))) * 1000000);
        $number = date("YmdHis");

        return $number . $micro;
    }

    /**
     * Encode params.
     *
     * @param array $params
     * @return string
     */
    private static function encode_params(array $params): string
    {
        return base64_encode(json_encode($params));
    }

    /**
     * Decode params.
     *
     * @param string $params
     * @return array
     */
    private static function decode_params(string $params): array
    {
        return json_decode(base64_decode($params), true);
    }

    /**
     * Signed string.
     *
     * @param string $str
     * @return string
     */
    private static function str_to_sign(string $str): string
    {
        return base64_encode(sha1($str, 1));
    }

    /**
     * Decode responce from Liqpay.
     *
     * @param string $data
     * @param string $signature
     * @return array
     */
    public static function decode_responce(string $data, string $signature): array
    {
        $public_key = config('app.liqpay_public_key');
        $private_key = config('app.liqpay_private_key');

        $sign =  self::str_to_sign($private_key . $data . $private_key);
        if ($sign !== $signature) {
            return [
                'status' => false,
                'error' => 'Сигнатура платежа не прошла проверку!'
            ];
        }

        $decode_data = self::decode_params($data);
        $decode_data['info'] = self::decode_params($decode_data['info']);
        if (empty($decode_data['info'])) {
            return [
                'status' => false,
                'error' => 'Нет дополнительных данных о переводе!'
            ];
        }

        return [
            'status' => true,
            'data'   => $decode_data,
        ];
    }
}
