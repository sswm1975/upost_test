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
        # Приоритеты определения языка:
        # - параметр запроса "lang";
        # - установленный язык в профиле пользователя;
        # - дефолтный язык (en).
        if ($request->filled('lang') && in_array($request->get('lang'), config('app.languages'))) {
            $lang = $request->get('lang');
        } elseif (isset($request->user()->lang)) {
            $lang = $request->user()->lang;
        } else {
            $lang = config('app.default_language');
        }

        App::setLocale($lang);

        Carbon::setLocale($lang);

        return $next($request);
    }
}
