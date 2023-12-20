<?php

use Illuminate\Routing\Router;

Route::view('/', 'welcome');

Route::get('payment', 'PaymentController@index');
Route::post('charge', 'PaymentController@charge');
Route::get('payment_success', 'PaymentController@payment_success')->name('payment_success');
Route::get('payment_error', 'PaymentController@payment_error')->name('payment_error');

# Wise events endpoints
Route::group([
    'prefix'     => 'wise',
    'as'         => 'wise.',
], function (Router $router) {
    $router->match(['get', 'post'], 'transfer_state_change', 'WiseEventsController@eventTransferStateChange')->name('transfer_state_change');
    $router->match(['get', 'post'], 'transfer_payout_failure', 'WiseEventsController@eventTransferPayoutFailure')->name('transfer_payout_failure');
});
