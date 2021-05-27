<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use DB;

class BasicAuthenticate
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    protected function attempt(array $credentials)
    {
        $login = $credentials['login'] ?? '';
        $password = $credentials['password'] ?? '';

        $is_email = Str::contains($login, '@');
        
        return (bool)DB::table('users')->where('user_password', $password)
            ->when($is_email, function ($query) use ($login) {
                return $query->where('user_email', $login);
            })       
            ->when(!$is_email, function ($query) use ($login) {
                return $query->where('user_phone', $login);
            })
            ->count();
    }

    /**
     * Get a validator for an incoming credentials.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $credentials)
    {
        return Validator::make($credentials,
            [
                'login'    => 'required',
                'password' => 'required',
            ],
            [
                'required' => ':attribute_is_empty'
            ]
        );        
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $credentials = [
            'login'    => $request->header('PHP-AUTH-USER'),
            'password' => $request->header('PHP-AUTH-PW'),
        ];

        $validator = $this->validator($credentials);

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()
            ]);            
        }

        if (!$this->attempt($credentials)) {
            return response()->json([
                'status' => 404, 
                'errors' => 'auth_fail'
            ]);
        }
        
        return $next($request);
    }
}
