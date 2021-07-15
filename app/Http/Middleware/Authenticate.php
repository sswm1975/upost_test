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
        throw new ErrorException(__('message.token_incorrect'));
    }
}
