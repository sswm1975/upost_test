<?php

use App\Mail\OrderBanEmail;
use App\Mail\SendTokenUserDataChange;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/emails/order_ban', function() {
    return new OrderBanEmail();
});

Route::get('/emails/token_user_data_change', function() {
    return new SendTokenUserDataChange('VERIFICATION_CODE');
});
