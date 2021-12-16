<?php

use App\Mail\OrderBanEmail;
use App\Mail\SendTokenUserDataChange;
use App\Mail\SocialChangePassword;
use App\Modules\Liqpay;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/emails/order_ban', function() {
    return new OrderBanEmail();
});

Route::get('/emails/token_user_data_change', function() {
    return new SendTokenUserDataChange('VERIFICATION_CODE');
});

Route::get('/emails/social_change_password', function() {
    return new SocialChangePassword([
        'provider' => 'google',
        'fullname' => 'Шкода Сергей',
        'email'    => 'sswm1975@gmail.com',
        'password' => Str::random(10),
        'url'      => 'https://post.tantal-web.top/log-in/',
    ]);
});

Route::get('/liqpay', function() {
    $params = Liqpay::create_params(
        19,
        'Пупкин Вася',
        1,
        5,
        'UAH',
        'Test payment',
        'uk'
    );

    \Log::info($params);

    $data = $params['data'];
    $signature = $params['signature'];

    return <<<HTML
<form method="POST" action="https://www.liqpay.ua/api/3/checkout" accept-charset="utf-8">
 <input type="hidden" name="data" value="$data">
 <input type="hidden" name="signature" value="$signature">
 <input type="image" src="https://static.liqpay.ua/buttons/p1ru.radius.png" alt="image">
</form>
HTML;
});
