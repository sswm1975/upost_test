<?php

namespace App\Modules\Parsers;

interface ParserInterface
{
    public function getProductName():string;
    public function getProductPrice():string;
    public function getProductCurrency():string;
    public function getProductImage():string;
    public function getProductImages():array;
}
