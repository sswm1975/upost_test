<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

# Авторизація
Route::post('login', function () {
    return response()->json([
        'status'  => 200,
        'message' => 'successfully_logged_in',
    ]);
})->middleware('auth.basic');

# Реєстрація
Route::post('register', 'API\Auth\RegisterController@register');

# Отримання інформації про користувача (тільки публічні дані)
Route::get('profile/{id}', 'API\ProfileController@getPublicData');

# Отримання інформації про користувача (всі дані)
Route::get('profile', 'API\ProfileController@getPrivateData')->middleware('auth.basic');

# Зміна даних профілю (тільки публічні дані)
Route::post('profile', 'API\ProfileController@updatePublicData')->middleware('auth.basic');

# Дані мов та валют
Route::match(['GET', 'POST'], 'language', API\LanguageController::class)->middleware('auth.basic');

# Отримання даних країни або міста: Отримувати назву країни по її ідентифікатору
Route::match(['GET', 'POST'], 'get_country', 'API\CountryController@getCountry')->middleware('auth.basic');
