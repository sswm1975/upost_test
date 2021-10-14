<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
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

        $token = $this->generateToken($user);

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

        $is_email = Str::contains($login, '@');

        return User::wherePassword($credentials['password'])
            ->when($is_email, function ($query) use ($login) {
                return $query->whereEmail($login);
            })
            ->when(!$is_email, function ($query) use ($login) {
                return $query->wherePhone($login);
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
        $request->user()
            ->forceFill(['api_token' => null])
            ->save();

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
            'phone'    => ['required', 'phone', 'unique:users'],
            'email'    => ['required', 'email', 'max:30', 'unique:users'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        $user = User::create($data);

        $token = $this->generateToken($user);

        return response()->json([
            'status'  => true,
            'message' => __('message.register_successful'),
            'token'   => $token,
        ]);
    }

    /**
     * Получить данные авторизированного пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function getAuthUser(Request $request): JsonResponse
    {
        $user = request()->user();

        if (!$user) throw new ErrorException(__('message.user_not_found'));

        unset($user->password, $user->api_token);

        return response()->json([
            'status' => true,
            'user'   => $user,
        ]);
    }

    /**
     * Простая проверка токена.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkToken(Request $request): JsonResponse
    {
        $exists = $request->filled('token') && User::existsToken(hash('sha256', $request->get('token')));

        return response()->json([
            'status' => $exists,
        ]);
    }

    /**
     * Генерация токена.
     *
     * @param User $user
     * @return string
     */
    private function generateToken(User $user): string
    {
        $token = Str::random(64);

        $user->forceFill([
            'api_token'   => hash('sha256', $token),
            'last_active' => Date::now(),
        ])->save();

        return $token;
    }
}
