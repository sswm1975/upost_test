<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('test/login', function () {
    $login    = 'test_email@gmail.com';
    $password = md5(md5('qwer'));

    return response()->json(test_api('http://upost.test/api/login', "$login:$password"));
});

Route::get('test/register', function () {
    $post_data = [
        'user_phone'    => '+380680091088',
        'user_email'    => 'test_email5@gmail.com',
        'user_password' => 'qwerty',
    ];

    return response()->json(test_api('http://upost.test/api/register', '', $post_data));
});