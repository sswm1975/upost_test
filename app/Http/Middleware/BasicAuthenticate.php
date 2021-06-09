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
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $credentials = [
            'login'    => $request->header('PHP-AUTH-USER', ''),
            'password' => $request->header('PHP-AUTH-PW', ''),
        ];

        $validator = $this->validator($credentials);

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all()
            ]);
        }

        $user = $this->login($credentials);

        if (empty($user)) {
            return response()->json([
                'status' => 404,
                'errors' => 'auth_fail'
            ]);
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
            ],
            config('validation.messages'),
            config('validation.attributes')
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
            ->exclude(['user_password'])
            ->first();
    }
}
