<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use App\Exceptions\ErrorException;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Handle an unauthenticated user.
     *
     * @param Request $request
     * @param array $guards
     * @return void
     *
     * @throws ErrorException
     */
    protected function unauthenticated($request, array $guards)
    {
        $lang = in_array($request->get('lang'), config('app.languages'))
            ? $request->get('lang')
            : config('app.default_language');

        app()->setLocale($lang);

        throw new ErrorException(__('message.token_incorrect'));
    }
}
