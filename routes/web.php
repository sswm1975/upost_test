<?php

use Illuminate\Support\Facades\Route;

use App\Modules\Parsers\Parser;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/parser', function() {
    $url = request('url');

    if (!$url) {
        return response()->json([
            'status' => false,
        ], 404);
    }

    $parser = (new Parser($url))->handler();

    return response()->json([
        'status'   => true,
        'name'     => $parser->getProductName(),
        'category' => $parser->getProductCategory(),
        'price'    => $parser->getProductPrice(),
        'size'     => $parser->getProductSize(),
        'weight'   => $parser->getProductWeight(),
        'image'    => $parser->getProductImage(),
        'favicon'  => $parser->getFavicon(),
    ]);
});
