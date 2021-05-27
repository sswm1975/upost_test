<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use DB;

class RegisterController extends Controller
{
    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, 
            [
                'user_phone'    => ['required', 'max:30', 'unique:users'],
                'user_email'    => ['required', 'email', 'max:30', 'unique:users'],
                'user_password' => ['required', 'min:6'],
            ],
            [           
                'required' => 'required_field',
                'unique'   => 'already_used',
                'max'      => 'too_long',
                'min'      => 'too_short',
                'email'    => 'not_valid',
            ]
        );
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'user_phone'       => $data['user_phone'],
            'user_email'       => $data['user_email'],
            'user_password'    => md5(md5($data['user_password'])),
            'user_name'        => $data['user_name'] ?? '',
            'user_surname'     => $data['user_surname'] ?? '',
            'user_card_number' => $data['user_card_number'] ?? '',
            'user_status'      => config('user.default.status'),
            'user_lang'        => $data['user_lang'] ?? config('user.default.lang'),
            'user_currency'    => $data['user_currency'] ?? config('user.default.currency'),
            'user_role'        => $data['user_role'] ?? config('user.default.role'),
            'user_validation'  => 'no_valid',
        ]);
    }

    /**
     * API Register user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()
            ]);            
        }       

        $user = $this->create($request->all());

        return response()->json([
            'status'  => 200,
            'message' => 'successfully_registered',
        ]);
    }

}
