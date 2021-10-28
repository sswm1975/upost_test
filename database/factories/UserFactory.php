<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\City;
use App\Models\User;
use Faker\Factory as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function () {
    $faker = Faker::create('Uk_UA');

    $gender = $faker->randomElement(['male', 'female']);
    $surname = $faker->lastName;

    return [
        'phone'         => $faker->unique()->e164PhoneNumber,
        'email'         => $faker->unique()->email,
        'password'      => '123456',
        'name'          => $faker->firstName($gender),
        'surname'       => $surname,
        'city_id'       => $faker->randomElement(City::pluck('id')->toArray()),
        'birthday'      => $faker->dateTimeInInterval('-50 years', '+30 years'),
        'gender'        => $gender,
        'card_number'   => $faker->creditCardNumber,
        'card_name'     => mb_strtoupper(Str::slug($surname)),
        'lang'          => $faker->randomElement(['uk', 'ru', 'en']),
        'currency'      => $faker->randomElement(config('app.currencies')),
        'validation'    => $faker->randomElement(['valid', 'no_valid']),
        'register_date' => $faker->dateTime(),
        'last_active'   => $faker->dateTimeInInterval('-150 days', '+150 days'),
        'resume'        => $faker->realText(500),
    ];
});
