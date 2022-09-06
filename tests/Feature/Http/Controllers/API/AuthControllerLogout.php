<?php

/**
 * AuthController: Тестирование метода "Logout" - прекращение сеанса авторизованного пользователя.
 */

namespace Tests\Feature\Http\Controllers\API;

use App\Libs\TestHelpers;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;

class AuthControllerLogout extends TestCase
{
    /**
     * Завершение сеанса не поддерживаемым методом (в запросе указан метод GET, а нужно POST).
     *
     * @return void
     */
    public function testBadAuthMethod()
    {
        $this->getJson(TestHelpers::LOGOUT_URI)
            ->assertStatus(SymfonyResponse::HTTP_METHOD_NOT_ALLOWED)
            ->assertJson(['message' => "The GET method is not supported for this route. Supported methods: POST."]);
    }

    /**
     * Завершить сеанс может только авторизованный пользователь (в запросе должен быть токен).
     *
     * @return void
     */
    public function testAuthMiddleware()
    {
        $this->postJson(TestHelpers::LOGOUT_URI)
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => ['The token is incorrect.']
            ]);
    }

    /**
     * Завершить сеанс с невалидным токеном.
     *
     * @return void
     */
    public function testWrongToken()
    {
        $this->postJson(TestHelpers::LOGOUT_URI, [], ['Authorization' => "Bearer BAD_TOKEN"])
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => ['The token is incorrect.']
            ]);
    }

    /**
     * Успешно завершить сеанс.
     *
     * @return void
     */
    public function testLogoutSuccessful()
    {
        TestHelpers::clearLoginAttempts();

        # успешно логинимся и получаем токен
        $token = $this->postJson(TestHelpers::LOGIN_URI, ['login' => TestHelpers::LOGIN_EMAIL_OK, 'password' => TestHelpers::PASSWORD_OK])
            ->assertOk()
            ->assertJsonStructure(['status', 'message', 'token'])
            ->assertJsonFragment(['status' => true])
            ->assertJsonFragment(['message' => 'Login successful.'])
            ->decodeResponseJson('token');

        # завершаем сеанс
        $this->postJson(TestHelpers::LOGOUT_URI, ['lang' => 'en'], ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertExactJson([
                'status'  => true,
                'message' => 'Logout successful.'
            ]);

        # проверяем, что в таблице users для аутентифицированного пользователя поле api_token стало пустым (токен удален)
        $this->assertDatabaseHas('users', [
            'email'       => TestHelpers::LOGIN_EMAIL_OK,
            'api_token'   => null,
        ]);
    }
}
