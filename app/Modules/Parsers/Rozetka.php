<?php

namespace App\Modules\Parsers;

class Rozetka extends ParserKernel implements ParserInterface
{
    protected array $config;
    protected array $product;
    protected array $category;

    public function __construct($link, $config = [])
    {
        $this->config = $config;

        parent::__construct($link);

        $this->init();
    }

    protected function init():void
    {
        $this->product = $this->getJsonDecode($this->config['product']);
        $this->category = $this->getJsonDecode($this->config['category']);
    }

    public function getProductName():string
    {
        return $this->product['name'] ?? '';
    }

    public function getProductCategory():string
    {
        return $this->category['ItemListElement'][0]['item']['name'] ?? '';
    }

    public function getProductPrice():string
    {
        return $this->product['offers']['price'] ?? '';
    }

    public function getProductCurrency():string
    {
        return getCurrencySymbol($this->product['offers']['priceCurrency'] ?? '');
    }

    public function getProductImage():string
    {
        if (empty($this->product['image']) || !filter_var($this->product['image'], FILTER_VALIDATE_URL)) {
            return '';
        }

        return $this->getImageToBase64($this->product['image']);
    }

    public function getProductImages():array
    {
        return [];
    }

    public function getProductSize():string
    {
        return '';
    }

    public function getProductWeight():string
    {
        return '';
    }
}
