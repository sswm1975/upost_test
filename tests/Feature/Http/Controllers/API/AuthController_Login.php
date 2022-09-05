<?php

namespace Tests\Feature\Http\Controllers\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;
use Faker\Factory as Faker;

/* Действительные логины и пароль */
DEFINE('LOGIN_EMAIL_OK', 'sswm@i.ua');
DEFINE('LOGIN_PHONE_OK', '+380978820043');
DEFINE('PASSWORD_OK', md5(md5('testtest')));

/* Фиктивный логин и пароль */
DEFINE('LOGIN_FAIL', 'test');
DEFINE('PASSWORD_FAIL', '123456');

/* Ендпоинт для Аутентификации по емейлу или телефону */
DEFINE('LOGIN_URI', '/api/auth/login');

/* Ендпоинт для Аутентификации через соц.сеть: Google или Facebook */
DEFINE('LOGIN_SOCIAL_URI', '/api/auth/social');

class AuthControllerLogin extends TestCase
{
    /**
     * Сбрасывание счетчика неудачных попыток для middleware('throttle:5,10') и локального IP 127.0.0.1
     *
     * @return void
     */
    protected static function clearLoginAttempts()
    {
        # см. как определяется ключ в методе resolveRequestSignature класса Illuminate\Routing\Middleware\ThrottleRequests
        $ip = request()->ip();
        $domain = optional(request()->route())->getDomain();
        $key = sha1("{$domain}|{$ip}");

        # подключаемся к классу RateLimiter, который может сбрасывать счетчик
        app(\Illuminate\Cache\RateLimiter::class)->clear($key);
    }

    /**
     * Аутентификация не поддерживаемым методом (в запросе указан метод GET, а нужно POST).
     *
     * @return void
     */
    public function testLogin_BadAuthMethod()
    {
        static::clearLoginAttempts();

        $this->getJson(LOGIN_URI, ['login' => LOGIN_EMAIL_OK, 'password' => PASSWORD_OK])
            ->assertStatus(SymfonyResponse::HTTP_METHOD_NOT_ALLOWED)
            ->assertJson(['message' => "The GET method is not supported for this route. Supported methods: POST."]);
    }

    /**
     * Аутентификация без указания логина и пароля.
     *
     * @return void
     */
    public function testLogin_NotAuthParams()
    {
        static::clearLoginAttempts();

        $this->postJson(LOGIN_URI)
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => [
                    'The login field is required.',
                    'The password field is required.'
                ]
            ]);
    }

    /**
     * Аутентификация без указания пароля.
     *
     * @return void
     */
    public function testLogin_NotAuthPassword()
    {
        static::clearLoginAttempts();

        $this->postJson(LOGIN_URI, ['login' => LOGIN_EMAIL_OK])
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => [
                    'The password field is required.'
                ]
            ]);
    }

    /**
     * Аутентификация без указания логина.
     *
     * @return void
     */
    public function testLogin_NotAuthLogin()
    {
        static::clearLoginAttempts();

        $this->postJson(LOGIN_URI, ['password' => PASSWORD_OK])
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => [
                    'The login field is required.',
                ]
            ]);
    }

    /**
     * Аутентификация с некорректным логином и паролем.
     *
     * @return void
     */
    public function testLogin_WrongCredentials()
    {
        static::clearLoginAttempts();

        $this->postJson(LOGIN_URI, ['login' => LOGIN_FAIL, 'password' => PASSWORD_FAIL])
            ->assertForbidden()
            ->assertExactJson([
                'status' => false,
                'errors' => [
                    'These credentials do not match our records.',
                ]
            ]);
    }

    /**
     * Аутентификация с некорректным логином.
     *
     * @return void
     */
    public function testLogin_WrongLogin()
    {
        static::clearLoginAttempts();

        $this->postJson(LOGIN_URI, ['login' => LOGIN_FAIL, 'password' => PASSWORD_OK])
            ->assertForbidden()
            ->assertExactJson([
                'status' => false,
                'errors' => [
                    'These credentials do not match our records.',
                ]
            ]);
    }

    /**
     * Аутентификация с некорректным паролем.
     *
     * @return void
     */
    public function testLogin_WrongPassword()
    {
        static::clearLoginAttempts();

        $this->postJson(LOGIN_URI, ['login' => LOGIN_EMAIL_OK, 'password' => PASSWORD_FAIL])
            ->assertForbidden()
            ->assertExactJson([
                'status' => false,
                'errors' => [
                    'These credentials do not match our records.',
                ]
            ]);
    }

    /**
     * Проверка аутентификации перебором пароля.
     * (Разрешается 5 попыток, при неудаче блокируется на 10 минут).
     *
     * @return void
     */
    public function testLogin_BruteForcePassword()
    {
        static::clearLoginAttempts();

        # первые 4 запроса будут отдавать ошибку 403
        foreach (range(0, 4) as $attempt) {
            $this->postJson(LOGIN_URI, ['login' => LOGIN_EMAIL_OK, 'password' => PASSWORD_FAIL . '_' . $attempt])
                ->assertStatus(SymfonyResponse::HTTP_FORBIDDEN)
                ->assertHeader('X-RATELIMIT-LIMIT', 5)                  # максимальное число запросов для приложения, разрешённое в данном интервале времени (5 попыток)
                ->assertHeader('X-RATELIMIT-REMAINING', 4 - $attempt);  # сколько запросов осталось в данном интервале времени
        }

        # последний запрос отдаст ошибку 429-Too Many Attempts
        $this->postJson(LOGIN_URI, ['login' => LOGIN_EMAIL_OK, 'password' => PASSWORD_FAIL])
            ->assertStatus(SymfonyResponse::HTTP_TOO_MANY_REQUESTS)
            ->assertHeader('X-RATELIMIT-LIMIT', 5)      # максимальное число запросов для приложения, разрешённое в данном интервале времени (5 попыток)
            ->assertHeader('X-RATELIMIT-REMAINING', 0)  # сколько запросов у вас осталось в данном интервале времени
            ->assertHeader('RETRY-AFTER', 600)          # сколько секунд надо ждать до следующей попытки (600 = 10 минут)
            ->assertExactJson([
                'status' => false,
                'errors' => [
                    'Too Many Attempts.',
                ]
            ]);

        static::clearLoginAttempts();
    }

    /**
     * Успешная аутентификация: логин - емейл.
     *
     * @return void
     */
    public function testLogin_AuthEmailSuccessful()
    {
        static::clearLoginAttempts();

        $this->postJson(LOGIN_URI, ['login' => LOGIN_EMAIL_OK, 'password' => PASSWORD_OK])
            ->assertStatus(SymfonyResponse::HTTP_OK)
            ->assertJsonStructure(['status', 'message', 'token'])
            ->assertJsonFragment(['status' => true])
            ->assertJsonFragment(['message' => 'Login successful.']);
    }

    /**
     * Успешная аутентификация: логин - телефон.
     *
     * @return void
     */
    public function testLogin_AuthPhoneSuccessful()
    {
        static::clearLoginAttempts();

        $this->postJson(LOGIN_URI, ['login' => LOGIN_PHONE_OK, 'password' => PASSWORD_OK])
            ->assertStatus(SymfonyResponse::HTTP_OK)
            ->assertJsonStructure(['status', 'message', 'token'])
            ->assertJsonFragment(['status' => true])
            ->assertJsonFragment(['message' => 'Login successful.']);
    }

    /**
     * Успешная аутентификация: В таблицу пользователя записан шифрованный api_token и время последнего доступа.
     *
     * @return void
     */
    public function testLogin_AuthBDSuccessful()
    {
        static::clearLoginAttempts();

        $response = $this->postJson(LOGIN_URI, ['login' => LOGIN_EMAIL_OK, 'password' => PASSWORD_OK])
            ->assertStatus(SymfonyResponse::HTTP_OK)
            ->assertJsonStructure(['status', 'message', 'token']);

        # полученный JSON-контент декодируем в ассоциативный массив
        $json = json_decode($response->getContent(), true);

        # токен клиенту отдается "чистый", а в таблице сохраняется хешированным
        $api_token = hash('sha256', $json['token']);

        # проверяем факт сохранения токена и последнего доступа в таблице users
        $this->assertDatabaseHas('users', [
            'email'       => LOGIN_EMAIL_OK,
            'api_token'   => $api_token,
            'last_active' => date('Y-m-d H:i:s'),
        ]);
    }
}
