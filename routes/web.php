<?php

use App\Modules\Liqpay;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Hello world!';
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
