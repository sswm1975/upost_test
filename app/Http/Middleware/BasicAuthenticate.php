<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class BasicAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws \App\Exceptions\ValidatorException
     */
    public function handle(Request $request, Closure $next)
    {
        $credentials = [
            'login'    => $request->header('PHP-AUTH-USER', ''),
            'password' => $request->header('PHP-AUTH-PW', ''),
        ];

        $validator = $this->validator($credentials);

        validateOrExit($validator);

        $user = $this->login($credentials);

        if (empty($user)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.auth_failed')]
            ], 404);
        }

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
    protected function login(array $credentials)
    {
        $login = $credentials['login'] ?? '';
        $password = $credentials['password'] ?? '';

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
