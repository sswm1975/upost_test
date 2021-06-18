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
        return 'Param url empty!';
    }
    $parser = (new Parser($url))->parser();

    echo 'NAME: ' . $parser->getProductName();
    echo "<br>";

    echo 'CATEGORY: ' . $parser->getProductCategory();
    echo "<br>";

    echo 'PRICE: ' . $parser->getProductPrice();
    echo "<br>";

    echo '<img src="' . $parser->getProductImage() . '">';
    echo "<br>";

    echo '<img src="' . $parser->getFavicon() . '">';
});
