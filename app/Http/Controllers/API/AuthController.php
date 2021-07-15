<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Авторизация пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = validateOrExit([
            'login'    => 'required',
            'password' => 'required',
        ]);

        $user = $this->attemptLogin($credentials);

        if (!$user) throw new ErrorException(__('message.auth_failed'));

        $token = Str::random(64);

        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return response()->json([
            'status'  => true,
            'message' => __('message.login_successful'),
            'token'   => $token,
        ]);
    }

    /**
     * Проверить указанные учетные данные.
     *
     * @param array $credentials
     * @return User|null
     */
    protected function attemptLogin(array $credentials = []): ?User
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

    /**
     * Прекращение сеанса авторизованного пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->update(['token' => null]);

        return response()->json([
            'status'  => true,
            'message' => __('message.logout_successful'),
        ]);
    }

    /**
     * Регистрация пользователя.
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

        User::create([
            'user_phone'    => $data['user_phone'],
            'user_email'    => $data['user_email'],
            'user_password' => getHashPassword($data['user_password']),
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('message.register_successful'),
        ]);
    }
}
