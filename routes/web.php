<?php

Route::view('/', 'welcome')->name('/');

Route::post('/payment', ['as' => 'payment', 'uses' => 'PaymentController@payWithpaypal']);
Route::get('/payment/status', ['as' => 'status', 'uses' => 'PaymentController@getPaymentStatus']);
