<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Jobs\UploadSocialPhoto;
use App\Mail\SocialChangePassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

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

        if (! $user) throw new ErrorException(__('message.auth_failed'), Response::HTTP_FORBIDDEN);

        $token = $this->generateToken($user);

        return response()->json([
            'status'  => true,
            'message' => __('message.login_successful'),
            'token'   => $token,
        ]);
    }

    /**
     * Авторизация через соц.сети (Google, Facebook).
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException|ValidationException|ValidatorException
     */
    public function social(Request $request): JsonResponse
    {
        # правила валидации
        $rules = [
            'provider'    => 'required|in:google,facebook',
            'identifier'  => 'required|numeric',
            'email'       => 'required|email',
            'displayName' => 'nullable|censor',
            'firstName'   => 'nullable|censor',
            'lastName'    => 'nullable|censor',
            'phone'       => 'nullable|censor',
            'language'    => 'nullable',
            'gender'      => 'nullable',
            'photoURL'    => 'nullable|url',
        ];

        # валидация
        $validator = Validator::make($request->all(), $rules);

        # если ошибка, то по всем правилам одинаковое сообщение: "These credentials do not match our records."
        if ($validator->fails()) {
            throw new ValidatorException(__('message.auth_failed'));
        }

        # получаем проверенные входные данные
        $data = $validator->validated();

        # поле с идентификатором соц.сети (google_id или facebook_id)
        $field_id = $data['provider'] . '_id';

        # ищем пользователя по идентификатору соц.сети в зависимости от провайдера
        if (! $user = User::withoutRemoved()->where($field_id, $data['identifier'])->first()) {
            # ищем пользователя по емейлу, который привязан к соц.сети
            if ($user = User::withoutRemoved()->whereEmail($data['email'])->first()) {
                if (empty($user->$field_id)) {
                    $user->$field_id = $data['identifier'];
                    $user->save();
                }
            } else {
                $password = Str::random(10);

                # создаём нового пользователя
                $user = $this->createSocialUser($field_id, $data, $password);

                $info = [
                    'language'    => $user->lang,
                    'provider'    => $data['provider'],
                    'client_name' => $data['displayName'],
                    'email'       => $data['email'],
                    'password'    => $password,
                    'url'         => rtrim(config('app.wordpress_url'), '/') . '/log-in/?change_password',
                ];

                try {
                    Mail::to($user->email)->send(new SocialChangePassword($info));
                } catch (\Exception $e) {
                    $msg = printf('При отправке почтового уведомления на почтовый ящик %s возникла ошибка %s', $user->email, $e->getMessage());
                    throw new ErrorException($msg, Response::HTTP_CONFLICT);
                }
            }
        }

        $token = $this->generateToken($user);

        return response()->json([
            'status'  => true,
            'message' => __('message.login_successful'),
            'token'   => $token,
        ]);
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
            'phone'    => 'required|phone|min:9|max:15|unique:users',
            'email'    => 'required|email|min:6|max:254|unique:users',
            'password' => 'required|min:6|max:30|confirmed',
            'check'    => 'required|accepted',
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
        $user = $request->user();

        if (! $user) throw new ErrorException(__('message.user_not_found'));

        return response()->json([
            'status' => true,
            'user'   => null_to_blank($user),
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
     * Проверить указанные учетные данные.
     *
     * @param array $credentials
     * @return User|null
     */
    protected function attemptLogin(array $credentials = []): ?User
    {
        $login = $credentials['login'];

        $is_email = Str::contains($login, '@');

        return User::query()
            ->withoutAppends()
            ->withoutRemoved()
            ->wherePassword($credentials['password'])
            ->when($is_email, function ($query) use ($login) {
                return $query->whereEmail($login);
            })
            ->when(!$is_email, function ($query) use ($login) {
                return $query->wherePhone($login);
            })
            ->first();
    }

    /**
     * Генерация токена.
     *
     * @param User $user
     * @return string
     */
    protected function generateToken(User $user): string
    {
        $token = Str::random(64);

        $user->forceFill([
            'api_token'   => hash('sha256', $token),
            'last_active' => Date::now(),
        ])->save();

        return $token;
    }

    /**
     * Создать пользователя на основании данных из соц.сети (Google/Facebook).
     *
     * @param string $field_id
     * @param array $data
     * @param string $password
     * @return User
     */
    protected function createSocialUser(string $field_id, array $data, string $password): User
    {
        $user = User::create([
            $field_id  => $data['identifier'],
            'email'    => $data['email'],
            'name'     => $data['firstName'] ?? '',
            'surname'  => $data['lastName'] ?? '',
            'phone'    => $data['phone'] ?? NULL,
            'lang'     => getLanguage($data['language'] ?? ''),
            'gender'   => getGender($data['gender'] ?? ''),
            'password' => $password,
        ]);

        if (!empty($data['photoURL'])) {
            UploadSocialPhoto::dispatch($user, $data['photoURL']);
        }

        return $user;
    }
}
