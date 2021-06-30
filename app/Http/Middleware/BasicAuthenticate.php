<?php

namespace App\Http\Middleware;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Closure;

class BasicAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function handle(Request $request, Closure $next)
    {
        $credentials = [
            'login'    => $request->header('PHP-AUTH-USER', ''),
            'password' => $request->header('PHP-AUTH-PW', ''),
        ];

        validateOrExit($this->validator($credentials));

        $user = $this->login($credentials);

        if (!$user) throw new ErrorException(__('message.auth_failed'));

        $request->setUserResolver(function() use ($user) {
            return $user;
        });

        return $next($request);
    }

    /**
     * Get a validator for an incoming credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $credentials): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($credentials,
            [
                'login'    => 'required',
                'password' => 'required',
            ]
        );
    }

    /**
     * Login a user using the given credentials.
     *
     * @param array $credentials
     * @return User|null
     */
    protected function login(array $credentials): ?User
    {
        $login = $credentials['login'];
        $password = $credentials['password'];

        $is_email = Str::contains($login, '@');

        return User::query()
            ->where('user_password', $password)
            ->when($is_email, function ($query) use ($login) {
                return $query->where('user_email', $login);
            })
            ->when(!$is_email, function ($query) use ($login) {
                return $query->where('user_phone', $login);
            })
            ->first();
    }
}
