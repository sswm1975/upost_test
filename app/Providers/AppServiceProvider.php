<?php

namespace App\Providers;

use App\Models\Rate;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
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
        }

        Schema::defaultStringLength(191);

        # Кэширование таблицы settings и занесение их в App Config, т.е. потом можно вызывать, к примеру config('default_language')
        if (Schema::hasTable('settings')) {
            Setting::initCache();
        }

        /**
         * Для операции "Сброс пароля" избавляемся от маршрута ниже (для API он не нужен):
         * Отобразить форму, содержащая поля email, password, password_confirmation и скрытое поле token.
         * Route::get('reset/{token}', 'API\ResetPasswordController@showResetForm')->name('password.reset')
         */
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $lang = request()->get('lang', config('app.default_language'));
            return env('WORDPRESS_URL').'password-reset/?token='.$token.'&language='.$lang;
        });

        /**
         * Валидация: Проверка международного телефонного номера.
         * Начинается с 00 или +, далее 11 и более цифр
         */
        Validator::extend('phone', function($attribute, $value, $parameters) {
            return preg_match('/^(00|\+)(\d{11,})$/', $value) === 1;
        });

        /**
         * Валидация: Проверка, что авторизированный пользователь имеет отношения к ставке.
         * Авторизированному пользователю должен принадлежать Заказ или Маршрут.
         */
        Validator::extend('owner_rate', function($attribute, $value, $parameters) {
            $rate = Rate::query()
                ->with('route:id,user_id', 'order:id,user_id')
                ->find($value, ['id', 'route_id', 'order_id']);

            if (!$rate) return false;

            return in_array(request()->user()->id, [$rate->order->user_id, $rate->route->user_id]);
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
