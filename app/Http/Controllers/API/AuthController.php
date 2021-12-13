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

        if (!$user) throw new ErrorException(__('message.auth_failed'), 403);

        $token = $this->generateToken($user);

        return response()->json([
            'status'  => true,
            'message' => __('message.login_successful'),
            'token'   => $token,
        ]);
    }

    /**
     * Гугл-авторизация.
     *
     * @throws ValidatorException|ValidationException
     */
    public function google(Request $request): JsonResponse
    {
        $data = validateOrExit(['user_data' => 'required']);
        $s=1;
        # ищем пользователя с идентификатором Гугла
        if (!$user = User::where('google_id', $data['identifier'])->first()) {
            # ищем пользователя с емейлом Гугла
            if ($user = User::where('email', $data['email'])->first()) {
                $s=2;
                if (empty($user->google_id)) {
                    $s=3;
                    $user->google_id = $data['identifier'];
                    $user->save();
                }
            } else {
                $s=4;
                # создаём нового пользователя
                $user = static::createGoogleUser($data);
            }
        }

        $token = $this->generateToken($user);

        return response()->json([
            'status'  => true,
            'message' => __('message.login_successful'),
            'token'   => $token,
            's' => $s,
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

        $user->loadCount(['orders', 'routes']);

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

    /**
     * Создать пользователя на основании данных из Гугла.
     *
     * @param $data
     * @return User
     */
    private static function createGoogleUser($data): User
    {
        return User::create([
            'google_id' => $data['identifier'],
            'email'     => $data['email'],
            'name'      => $data['firstName'],
            'surname'   => $data['lastName'],
            'phone'     => $data['phone'],
            'lang'      => getLanguage($data['language']),
            'gender'    => getGender($data['gender']),
        ]);
    }
}
