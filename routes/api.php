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

Route::post('login', function () {
    return response()->json([
        'status'  => 200,
        'message' => 'successfully_logged_in',
    ]);
})->middleware('auth.basic');

Route::post('register', 'API\Auth\RegisterController@register');

Route::get('profile/{id}', 'API\ProfilePublicController');
