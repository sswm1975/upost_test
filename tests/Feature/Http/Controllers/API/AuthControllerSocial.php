<?php

/**
 * AuthController: Тестирование метода "social" - аутентификация через социальную сеть (Google, Facebook).
 */

namespace Tests\Feature\Http\Controllers\API;

use App\Libs\TestHelpers;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;
use Faker\Factory as Faker;

class AuthControllerSocial extends TestCase
{
    /**
     * Аутентификация не поддерживаемым методом (в запросе указан метод GET, а нужно POST).
     *
     * @return void
     */
    public function testBadAuthMethod()
    {
        TestHelpers::clearLoginAttempts();

        $this->getJson(TestHelpers::LOGIN_SOCIAL_URI)
            ->assertStatus(SymfonyResponse::HTTP_METHOD_NOT_ALLOWED)
            ->assertJson(['message' => "The GET method is not supported for this route. Supported methods: POST."]);
    }

    /**
     * Не указаны обязательные параметры (провайдер, идентификатор, email) или они пустые.
     *
     * @return void
     */
    public function testNotAuthParams()
    {
        /* Функция формирования возможных комбинаций массива, взято с https://ru.stackoverflow.com/questions/955897/возможные-комбинации-массива-php */
        function tuples(array $arr, array &$res, array $prefix = [], $offset = 0) {
            for ($i = $offset; $i < count($arr); $i++) {
                $nextPrfx = array_merge($prefix, [$arr[$i]]);
                array_push($res, $nextPrfx);
                tuples($arr, $res, $nextPrfx, ++$offset);
            }
        }

        TestHelpers::clearLoginAttempts();

        # проверяем без параметров
        $this->postJson(TestHelpers::LOGIN_SOCIAL_URI)
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => ['These credentials do not match our records.']
            ]);

        # с тремя параметрами 'provider', 'identifier', 'email' формируем возможные комбинации массива - всего 7 вариантов
        $combinations = [];
        TestHelpers::tuples(['provider', 'identifier', 'email'], $combinations);

        # перебираем все варианты и проверяем ответ
        foreach ($combinations as $params) {
            TestHelpers::clearLoginAttempts();

            $this->postJson(TestHelpers::LOGIN_SOCIAL_URI, $params)
                ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
                ->assertExactJson([
                    'status' => false,
                    'errors' => ['These credentials do not match our records.']
                ]);
        }
    }

    /**
     * Не верный провайдер.
     *
     * @return void
     */
    public function testWrongProvider()
    {
        TestHelpers::clearLoginAttempts();

        $faker = Faker::create('Uk_UA');

        $params = [
            'provider'   => $faker->word(),
            'identifier' => $faker->numberBetween(),
            'email'      => $faker->unique()->email,
        ];

        $this->postJson(TestHelpers::LOGIN_SOCIAL_URI, $params)
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => ['These credentials do not match our records.']
            ]);
    }

    /**
     * Проверка на троттлинг.
     * (Разрешается 5 попыток, при неудаче блокируется на 10 минут).
     *
     * @return void
     */
    public function testThrottling()
    {
        TestHelpers::clearLoginAttempts();

        # первые 4 запроса будут отдавать ошибку 400
        foreach (range(0, 4) as $attempt) {
            $this->postJson(TestHelpers::LOGIN_SOCIAL_URI)
                ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
                ->assertHeader('X-RATELIMIT-LIMIT', 5)                  # максимальное число запросов для приложения, разрешённое в данном интервале времени (5 попыток)
                ->assertHeader('X-RATELIMIT-REMAINING', 4 - $attempt);  # сколько запросов осталось в данном интервале времени
        }

        # последний запрос отдаст ошибку 429-Too Many Attempts
        $this->postJson(TestHelpers::LOGIN_SOCIAL_URI)
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
     * Успешная регистрация Google-пользователя.
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthGoogleSuccessful()
    {
        TestHelpers::clearLoginAttempts();

        $params = [
            "provider"    => "google",
            "identifier"  => "105307229145456687654",
            "email"       => "artemogirock@gmail.com",
            "displayName" => "Артем Ткачик",
            "firstName"   => "Артем",
            "lastName"    => "Ткачик",
            "phone"       => "+380680091000",
            "language"    => "uk",
            "gender"      => "male",
            "photoURL"    => "https://lh3.googleusercontent.com/a-/AOh14GgGHa4agngLi6uMtCuNT4bJZaEMGHxCCTZQ2SzpMA=s96-c",
        ];

        # получаем ответ
        $token = $this->postJson(TestHelpers::LOGIN_SOCIAL_URI, $params)
            ->assertOk()
            ->assertJsonStructure(['status', 'message', 'token'])
            ->assertJsonFragment(['status' => true])
            ->assertJsonFragment(['message' => 'Login successful.'])
            ->decodeResponseJson('token');

        # проверяем факт регистрации нового google пользователя
        $this->assertDatabaseHas('users', [
            'api_token'     => hash('sha256', $token), # токен в таблице сохраняется хешированным
            'google_id'     => $params['identifier'],
            'email'         => $params['email'],
            'phone'         => $params['phone'],
            'name'          => $params['firstName'],
            'surname'       => $params['lastName'],
            'lang'          => $params['language'],
            'gender'        => $params['gender'],
            'status'        => 'active',
            'currency'      => '$',
            'validation'    => 'no_valid',
            'role'          => 'user',
            'register_date' => date('Y-m-d'),
            'last_active'   => date('Y-m-d H:i:s'),
        ]);

        # получаем из таблицы данные об аватаре пользователя
        $user = User::withoutAppends()->where('google_id', '=', $params['identifier'])->first(['id', 'photo']);

        # проверяем, что сохранилось фото пользователя
        $this->fileExists(asset("storage/{$user->id}/user/{$user->photo}"));

        # удаляем в таблице users тестового google пользователя
        $user->delete();
    }

    /**
     * Успешная регистрация Facebook-пользователя.
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthFacebookSuccessful()
    {
        TestHelpers::clearLoginAttempts();

        $params = [
            "provider"    => "facebook",
            "identifier"  => "104923752051320",
            "email"       => "upost.api@gmail.com",
            "displayName" => "Upost Api",
            "firstName"   => "Upost",
            "lastName"    => "Api",
            "phone"       => null,
            "language"    => null,
            "gender"      => null,
            "photoURL"    => "https://graph.facebook.com/v2.12/104923752051320/picture?width=150&height=150",
        ];

        # успешно логинимся и получаем токен
        $token = $this->postJson(TestHelpers::LOGIN_SOCIAL_URI, $params)
            ->assertOk()
            ->assertJsonStructure(['status', 'message', 'token'])
            ->assertJsonFragment(['status' => true])
            ->assertJsonFragment(['message' => 'Login successful.'])
            ->decodeResponseJson('token');

        # проверяем факт регистрации facebook пользователя
        $this->assertDatabaseHas('users', [
            'api_token'     => hash('sha256', $token), # токен в таблице сохраняется хешированным
            'facebook_id'   => $params['identifier'],
            'email'         => $params['email'],
            'phone'         => null,
            'name'          => $params['firstName'],
            'surname'       => $params['lastName'],
            'lang'          => 'en',
            'gender'        => 'unknown',
            'status'        => 'active',
            'currency'      => '$',
            'validation'    => 'no_valid',
            'role'          => 'user',
            'register_date' => date('Y-m-d'),
            'last_active'   => date('Y-m-d H:i:s'),
        ]);

        # получаем из таблицы данные об аватаре пользователя
        $user = User::withoutAppends()->where('facebook_id', '=', $params['identifier'])->first(['id', 'photo']);

        # проверяем, что сохранилось фото пользователя
        $this->fileExists(asset("storage/{$user->id}/user/{$user->photo}"));

        # удаляем в таблице users тестового facebook пользователя
        $user->delete();
    }
}
