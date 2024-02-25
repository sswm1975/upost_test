<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCurrency
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        # Приоритеты определения валюты:
        # - валюта указанна в параметре запроса;
        # - валюта из профиля пользователя;
        # - дефолтный валюта ($).
        if ($request->filled('currency') && in_array($request->input('currency'), config('app.currencies'))) {
            $currency = $request->input('currency');
        } elseif (isset($request->user()->currency)) {
            $currency = $request->user()->currency;
        } else {
            $currency = config('app.default_currency');
        }

        # заносим валюту в конфиг, использование config('currency')
        config(compact('currency'));

        return $next($request);
    }
}
