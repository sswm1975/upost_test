<?php

namespace App\Providers;

use App\Models\Currency;
use App\Models\NoticeType;
use App\Models\Constant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\Notifications\ResetPassword;
use Pavelpage\Censorship\Censor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        require_once __DIR__.'/../Libs/helpers.php';
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->isLocal() || config('app.debug')) {
            DB::enableQueryLog();
            Mail::alwaysTo('sswm@i.ua');
        }

        Schema::defaultStringLength(191);

        # Кэширование таблицы constants и занесение их в App Config, т.е. потом можно вызывать, к примеру config('default_language')
        Constant::initCache();

        # Кэширование текущих курсов из таблицы currencies и занесение их в App Config, т.е. потом можно вызывать, к примеру config('rates.$')
        Currency::initCache();

        # Кэширование типов уведомлений из таблицы notice_types и занесение их в App Config, т.е. потом можно вызывать, к примеру config('rates.$')
        NoticeType::initCache();

        /**
         * Для операции "Сброс пароля" избавляемся от маршрута ниже (для API он не нужен):
         * Отобразить форму, содержащая поля email, password, password_confirmation и скрытое поле token.
         * Route::get('reset/{token}', 'API\ResetPasswordController@showResetForm')->name('password.reset')
         */
        ResetPassword::createUrlUsing(function ($user, string $token) {
            # WordPress не надсилає HTTP_REFERER, для нього свої налаштування, REACT надсилає HTTP_REFERER - для нього свої налаштування
            if (empty(request()->header('referer'))) {
                $domain = config('app.wordpress_url');
                $end_point = 'new-password';
            } else {
                $domain = request()->header('referer');
                $end_point = 'forgot-password';
            }
            $lang = request()->get('lang', config('app.default_language'));
            return rtrim($domain, '/') . "/{$end_point}/?token={$token}&lang={$lang}";
        });

        /**
         * Валидация: Проверка международного телефонного номера.
         * Начинается с 00 или +, далее 11 и более цифр
         */
        Validator::extend('phone', function($attribute, $value, $parameters) {
            return preg_match('/^(00|\+)(\d{11,})$/', $value) === 1;
        });

        /**
         * Валидация: Проверка названия города (Regular Expressions for City name).
         * Взято с https://stackoverflow.com/questions/11757013/regular-expressions-for-city-name
         */
        Validator::extend('city_name', function($attribute, $value, $parameters) {
            return preg_match('/^[a-zA-Z]+(?:[\s-][a-zA-Z]+)*$/', $value) === 1;
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
            if (empty($value)) {
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

        /**
         * Валидация: Проверка на нецензурные выражения.
         */
        Validator::extend('censor', function ($attribute, $value, $parameters) {
            return !(new Censor())->hasObsceneWords($value);
        });

        /**
         * Валидация: Проверка на наличие контактных данных (телефона).
         */
        Validator::extend('not_phone', function ($attribute, $value, $parameters) {
            return !preg_match('/(\s*)?(\+)?([- _():=+]?\d[- _():=+]?){10,14}(\s*)?/m', $value);
        });
    }
}
