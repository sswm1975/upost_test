<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use Faker\Factory as Faker;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('Uk_UA');

        factory(User::class, 5)->create()->each(function($user) use ($faker) {
            Storage::disk('public')->makeDirectory("{$user->id}/user/");
            $user->photo = $faker->image("public/storage/{$user->id}/user/", 200, 200, 'cats', false);
            $user->save();
        });
    }
}
