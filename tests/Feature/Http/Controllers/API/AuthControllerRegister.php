<?php

/**
 * AuthController: Тестирование метода "register" - регистрация нового пользователя.
 */

namespace Tests\Feature\Http\Controllers\API;

use App\Libs\TestHelpers;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;

class AuthControllerRegister extends TestCase
{
    /**
     * Регистрация пользователя не поддерживаемым методом (в запросе указан метод GET, а нужно POST).
     *
     * @return void
     */
    public function testBadAuthMethod()
    {
        $this->getJson(TestHelpers::REGISTER_URI)
            ->assertStatus(SymfonyResponse::HTTP_METHOD_NOT_ALLOWED)
            ->assertJson(['message' => "The GET method is not supported for this route. Supported methods: POST."]);
    }

    /**
     * Регистрация без обязательных параметров или эти параметры пустые.
     *
     * @return void
     */
    public function testEmptyParams()
    {
         TestHelpers::clearLoginAttempts();

        # регистрация без параметров
        $this->postJson(TestHelpers::REGISTER_URI)
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => [
                    'The phone field is required.',
                    'The email field is required.',
                    'The password field is required.',
                    'The Terms of agreement field is required.',
                ],
            ]);

        # с пятью параметрами 'phone', 'email', 'password', 'password_confirmation', 'check' формируем возможные комбинации массива - всего 31 вариантов
        $combinations = [];
        TestHelpers::tuples(['phone', 'email', 'password', 'password_confirmation', 'check'], $combinations);

        # перебираем все варианты с пустыми параметрами
        foreach ($combinations as $params) {
            TestHelpers::clearLoginAttempts();

            $this->postJson(TestHelpers::REGISTER_URI, $params)
                ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
                ->assertJsonStructure(['status', 'errors'])
                ->assertJsonFragment(['status' => false]);
        }

        # генерим корректные входные данные
        $faker = Faker::create('Uk_UA');
        $password = $faker->password();
        $params = [
            'phone'                 => $faker->unique()->e164PhoneNumber,
            'email'                 => $faker->unique()->email,
            'password'              => $password,
            'password_confirmation' => $password,
            'check'                 => true,
        ];

        $errors = [
            'phone'                 => 'The phone field is required.',
            'email'                 => 'The email field is required.',
            'password'              => 'The password field is required.',
            'password_confirmation' => 'The password confirmation does not match.',
            'check'                 => 'The Terms of agreement field is required.',
        ];

        # тестируем без одного обязательного параметра
        foreach (array_keys($params) as $key) {
            $test_params = array_filter($params, function($k) use ($key) {
                return $key != $k;
            }, ARRAY_FILTER_USE_KEY);

            TestHelpers::clearLoginAttempts();

            $this->postJson(TestHelpers::REGISTER_URI, $test_params)
                ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
                ->assertJson([
                    'status' => false,
                    'errors' => [$errors[$key]],
                ]);
        }
    }

    /**
     * Успешная регистрация.
     *
     * @return void
     * @throws \Throwable
     */
    public function testRegistrationSuccessful()
    {
        # генерим корректные входные данные
        $faker = Faker::create('Uk_UA');
        $password = $faker->password();
        $params = [
            'phone'                 => $faker->unique()->e164PhoneNumber,
            'email'                 => $faker->unique()->email,
            'password'              => $password,
            'password_confirmation' => $password,
            'check'                 => true,
        ];

        TestHelpers::clearLoginAttempts();

        # успешно регистрируемся и получаем токен
        $token = $this->postJson(TestHelpers::REGISTER_URI, $params)
            ->assertOk()
            ->assertJsonStructure(['status', 'message', 'token'])
            ->assertJsonFragment(['status' => true])
            ->assertJsonFragment(['message' => 'Register successful.'])
            ->decodeResponseJson('token');

        # проверяем, что в таблице users добавлен пользователь
        $this->assertDatabaseHas('users', [
            'phone'                 => $params['phone'],
            'email'                 => $params['email'],
            'password'              => getHashPassword($params['password']),
            'name'                  => null,
            'surname'               => null,
            'city_id'               => null,
            'status'                => 'active',
            'card_number'           => null,
            'card_name'             => null,
            'birthday'              => null,
            'gender'                => 'unknown',
            'lang'                  => 'en',
            'currency'              => '$',
            'validation'            => 'no_valid',
            'role'                  => 'user',
            'photo'                 => null,
            'resume'                => null,
            'wallet'                => 0.00,
            'scores_count'          => 0,
            'reviews_count'         => 0,
            'failed_delivery_count' => 0,
            'failed_receive_count'  => 0,
            'api_token'             => hash('sha256', $token),
            'google_id'             => null,
            'facebook_id'           => null,
            'register_date'         => date('Y-m-d'),
            'last_active'           => date('Y-m-d H:i:s'),
        ]);

        # удаляем в таблице users тестового пользователя
        DB::table('users')->where('email', '=', $params['email'])->delete();
    }
}
