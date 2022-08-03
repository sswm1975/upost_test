<?php

namespace App\Modules\Parsers;

class Rozetka extends ParserKernel implements ParserInterface
{
    protected array $config;
    protected array $product;

    public function __construct($link, $config = [])
    {
        $this->config = $config;

        parent::__construct($link);

        $this->init();
    }

    protected function init():void
    {
        $this->product = $this->getJsonDecode($this->config['product']);
    }

    public function getProductName():string
    {
        return $this->product['name'] ?? '';
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
        if (empty($this->product['image'][0]) || !filter_var($this->product['image'][0], FILTER_VALIDATE_URL)) {
            return '';
        }

        return $this->getImageToBase64($this->product['image'][0]);
    }

    public function getProductImages():array
    {
        if (empty($this->product['image'])) {
            return [];
        }

        $urls = $this->handlerImages($this->config['images_handlers'], $this->product['image']);

        $images = [];
        foreach ($urls as $url) {
            $images[] = $this->getImageToBase64($url);
        }

        return $images;
    }
}
