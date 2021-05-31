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
        Validator::extend('phone', function($attribute, $value, $parameters){
            return preg_match('/^(00|\+)(\d{12,})$/', $value) === 1;
        });
    }
}
