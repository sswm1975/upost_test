<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * API Register user.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function register(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'user_phone'    => ['required', 'phone', 'unique:users'],
            'user_email'    => ['required', 'email', 'max:30', 'unique:users'],
            'user_password' => ['required', 'min:6', 'confirmed'],
        ]);

        $this->create($data);

        return response()->json([
            'status'  => true,
            'message' => __('message.register_successful'),
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data): User
    {
        return User::create([
            'user_phone'       => $data['user_phone'],
            'user_email'       => $data['user_email'],
            'user_password'    => getHashPassword($data['user_password']),
            'user_name'        => $data['user_name'] ?? '',
            'user_surname'     => $data['user_surname'] ?? '',
            'user_card_number' => $data['user_card_number'] ?? '',
            'user_status'      => config('user.default.status'),
            'user_lang'        => $data['user_lang'] ?? config('user.default.lang'),
            'user_currency'    => $data['user_currency'] ?? config('user.default.currency'),
            'user_role'        => $data['user_role'] ?? config('user.default.role'),
        ]);
    }
}
