<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLanguage
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
        $lang = config('app.default_language');
/*
! Тимчасово встановлюємо англійську мову, незалежно від параметру запиту чи налаштувань користувача !

        # Приоритеты определения языка:
        # - параметр запроса "lang";
        # - установленный язык в профиле пользователя;
        # - дефолтный язык (en).
        if ($request->filled('lang') && in_array($request->input('lang'), config('app.languages'))) {
            $lang = $request->input('lang');
        } elseif (isset($request->user()->lang)) {
            $lang = $request->user()->lang;
        } else {
            $lang = config('app.default_language');
        }
*/

        App::setLocale($lang);

        Carbon::setLocale($lang);

        return $next($request);
    }
}
