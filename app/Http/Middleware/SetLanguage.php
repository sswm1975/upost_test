<?php

namespace App\Http\Middleware;

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
        $lang = in_array($request->get('lang'), config('app.languages'))
            ? $request->get('lang')
            : config('app.default_language');

        App::setLocale($lang);

        return $next($request);
    }
}
