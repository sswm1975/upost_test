<?php

Route::view('/', 'welcome');

Route::get('payment', 'PaymentController@index');
Route::post('charge', 'PaymentController@charge');
Route::get('payment_success', 'PaymentController@payment_success')->name('payment_success');
Route::get('payment_error', 'PaymentController@payment_error')->name('payment_error');
