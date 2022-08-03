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
        $json = $this->findFirst($this->config['product']);

        $this->product = $json ? json_decode(utf8_decode($json), true) : [];
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

        return convertImageToBase64($this->product['image'][0]);
    }

    public function getProductImages():array
    {
        if (empty($this->product['image'])) {
            return [];
        }

        $urls = $this->handlerImages($this->config['images_handlers'], $this->product['image']);

        $images = [];
        foreach ($urls as $url) {
            $images[] = convertImageToBase64($url);
        }

        return $images;
    }
}
