<?php

use App\Mail\OrderBanEmail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/emails/order_ban', function() {
    return new OrderBanEmail();
});
