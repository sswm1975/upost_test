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
        $lang = config('app.default_language');
        if ($request->filled('lang')) {
            $lang = strtolower(substr($request->input('lang'), 0, 2));
            if (!in_array($lang, config('app.languages')) && isset($request->user()->lang)) {
                $lang = $request->user()->lang;
            }
        }

        App::setLocale($lang);

        Carbon::setLocale($lang);

        return $next($request);
    }
}
