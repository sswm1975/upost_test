<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Libs\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;
use Faker\Factory as Faker;

/* Ендпоинт для Аутентификации через соц.сеть: Google или Facebook */
DEFINE('LOGIN_SOCIAL_URI', '/api/auth/social');

class AuthControllerSocial extends TestCase
{
    /**
     * Аутентификация через соц.сеть: Не указаны обязательные параметры (провайдер, идентификатор, email) или они пустые.
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
        $this->postJson(LOGIN_SOCIAL_URI)
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => ['These credentials do not match our records.']
            ]);

        # с тремя параметрами 'provider', 'identifier', 'email' формируем возможные комбинации массива - всего 7 вариантов
        $combinations = [];
        tuples(['provider', 'identifier', 'email'], $combinations);

        # перебираем все варианты и проверяем ответ
        foreach ($combinations as $params) {
            TestHelpers::clearLoginAttempts();

            $this->postJson(LOGIN_SOCIAL_URI, $params)
                ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
                ->assertExactJson([
                    'status' => false,
                    'errors' => ['These credentials do not match our records.']
                ]);
        }
    }

    /**
     * Аутентификация через соц.сеть: Не верный провайдер.
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

        $this->postJson(LOGIN_SOCIAL_URI, $params)
            ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
            ->assertExactJson([
                'status' => false,
                'errors' => ['These credentials do not match our records.']
            ]);
    }

    /**
     * Аутентификация через соц.сеть: Проверка на троттлинг.
     * (Разрешается 5 попыток, при неудаче блокируется на 10 минут).
     *
     * @return void
     */
    public function testThrottling()
    {
        TestHelpers::clearLoginAttempts();

        # первые 4 запроса будут отдавать ошибку 400
        foreach (range(0, 4) as $attempt) {
            $this->postJson(LOGIN_SOCIAL_URI)
                ->assertStatus(SymfonyResponse::HTTP_BAD_REQUEST)
                ->assertHeader('X-RATELIMIT-LIMIT', 5)                  # максимальное число запросов для приложения, разрешённое в данном интервале времени (5 попыток)
                ->assertHeader('X-RATELIMIT-REMAINING', 4 - $attempt);  # сколько запросов осталось в данном интервале времени
        }

        # последний запрос отдаст ошибку 429-Too Many Attempts
        $this->postJson(LOGIN_SOCIAL_URI)
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
}
