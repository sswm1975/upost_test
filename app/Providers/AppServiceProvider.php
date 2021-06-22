<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require_once __DIR__.'/../Libs/helpers.php';
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Валидация: Проверка международного телефонного номера.
         * Начинается с 00 или +, далее 12 и более цифр
         */
        Validator::extend('phone', function($attribute, $value, $parameters) {
            return preg_match('/^(00|\+)(\d{12,})$/', $value) === 1;
        });

        /**
         * Валидация: Проверка base64 image-контента.
         */
        Validator::extend('base64_image', function ($attribute, $value, $parameters) {
            return validate_base64($value, ['jpg', 'jpeg'], 3000000, 2000, 2000);
        });

        /**
         * Валидация: Запись существует в таблице или значение равно 0 или пустое.
         */
        Validator::extend('exists_or_null', function ($attribute, $value, $parameters) {
            if($value == 0 || is_null($value)) {
                return true;
            } else {
                $validator = Validator::make([$attribute => $value], [
                    $attribute => 'exists:' . implode(",", $parameters)
                ]);
                return !$validator->fails();
            }
        });

        /**
         * Валидация: Проверка корректности номера банковской пластиковой карты.
         */
        Validator::extend('bankcard', function($attribute, $value, $parameters) {
            # в ПК должны быть только цифры
            if ( $value != preg_replace('/[^\d]/','', $value) ) {
                return false;
            }

            # проверяем платежную систему: 4 — VISA, 5 — MasterCard
            if (! in_array(substr($value, 0, 1), ['4', '5']) ) {
                return false;
            }

            # банковская карта состоит из 16 цифр
            if ( strlen($value) != 16 ) {
                return false;
            }

            # переворачивам строку задом наперед
            $s = strrev($value);

            # вычисление контрольной суммы
            $sum = 0;
            for ($i = 0; $i < strlen($s); $i++) {
                # использовать четные цифры как есть
                if (($i % 2) == 0) {
                    $val = $s[$i];
                } else {
                    # удвоить нечетные цифры и вычесть 9, если они больше 9
                    $val = $s[$i] * 2;
                    if ($val > 9) $val -= 9;
                }
                $sum += $val;
            }

            # число корректно, если сумма равна 10
            return (($sum % 10) == 0);
        });
    }
}
