<?php

/**
 * AuthController: Тестирование стандартной аутентификации (электронный почтовый ящик или номер телефона + пароль).
 */

namespace Tests\Feature\Http\Controllers\API;

use App\Libs\TestHelpers;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;

class AuthControllerLogin extends TestCase
{
    /**
     * Аутентификация не поддерживаемым методом (в запросе указан метод GET, а нужно POST).
     *
     * @return void
     */
    public function testBadAuthMethod()
    {
        TestHelpers::clearLoginAttempts();

        $this->getJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_EMAIL_OK, 'password' => TestHelpers::PASSWORD_OK])
            ->assertStatus(SymfonyResponse::HTTP_METHOD_NOT_ALLOWED)
            ->assertJson(['message' => "The GET method is not supported for this route. Supported methods: POST."]);
    }

    /**
     * Аутентификация без указания логина и пароля.
     *
     * @return void
     */
    public function testNotAuthParams()
    {
        TestHelpers::clearLoginAttempts();

        $this->postJson(TestHelpers::LOGIN_URI)
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
    public function testNotAuthPassword()
    {
        TestHelpers::clearLoginAttempts();

        $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_EMAIL_OK])
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
    public function testNotAuthLogin()
    {
        TestHelpers::clearLoginAttempts();

        $this->postJson(TestHelpers::LOGIN_URI, ['password' => TestHelpers::PASSWORD_OK])
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
    public function testWrongCredentials()
    {
        TestHelpers::clearLoginAttempts();

        $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_FAIL, 'password' => TestHelpers::PASSWORD_FAIL])
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
    public function testWrongLogin()
    {
        TestHelpers::clearLoginAttempts();

        $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_FAIL, 'password' => TestHelpers::PASSWORD_OK])
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
    public function testWrongPassword()
    {
        TestHelpers::clearLoginAttempts();

        $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_EMAIL_OK, 'password' => TestHelpers::PASSWORD_FAIL])
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
    public function testBruteForcePassword()
    {
        TestHelpers::clearLoginAttempts();

        # первые 4 запроса будут отдавать ошибку 403
        foreach (range(0, 4) as $attempt) {
            $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_EMAIL_OK, 'password' => TestHelpers::PASSWORD_FAIL . '_' . $attempt])
                ->assertStatus(SymfonyResponse::HTTP_FORBIDDEN)
                ->assertHeader('X-RATELIMIT-LIMIT', 5)                  # максимальное число запросов для приложения, разрешённое в данном интервале времени (5 попыток)
                ->assertHeader('X-RATELIMIT-REMAINING', 4 - $attempt);  # сколько запросов осталось в данном интервале времени
        }

        # последний запрос отдаст ошибку 429-Too Many Attempts
        $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_EMAIL_OK, 'password' => TestHelpers::PASSWORD_FAIL])
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

        TestHelpers::clearLoginAttempts();
    }

    /**
     * Успешная аутентификация: логин - емейл.
     *
     * @return void
     */
    public function testAuthEmailSuccessful()
    {
        TestHelpers::clearLoginAttempts();

        $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_EMAIL_OK, 'password' => TestHelpers::PASSWORD_OK])
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
    public function testAuthPhoneSuccessful()
    {
        TestHelpers::clearLoginAttempts();

        $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_PHONE_OK, 'password' => TestHelpers::PASSWORD_OK])
            ->assertStatus(SymfonyResponse::HTTP_OK)
            ->assertJsonStructure(['status', 'message', 'token'])
            ->assertJsonFragment(['status' => true])
            ->assertJsonFragment(['message' => 'Login successful.']);
    }

    /**
     * Успешная аутентификация: В таблицу пользователя записан шифрованный api_token и время последнего доступа.
     *
     * @return void
     * @throws \Throwable
     */
    public function testAuthBDSuccessful()
    {
        TestHelpers::clearLoginAttempts();

        # успешно логинимся и получаем токен
        $token = $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_EMAIL_OK, 'password' => TestHelpers::PASSWORD_OK])
            ->assertStatus(SymfonyResponse::HTTP_OK)
            ->assertJsonStructure(['status', 'message', 'token'])
            ->decodeResponseJson('token');

        # проверяем факт сохранения токена и последнего доступа в таблице users
        $this->assertDatabaseHas('users', [
            'email'       => TestHelpers::LOGIN_EMAIL_OK,
            'api_token'   => hash('sha256', $token),  # токен в таблице сохраняется хешированным
            'last_active' => date('Y-m-d H:i:s'),
        ]);
    }
}
