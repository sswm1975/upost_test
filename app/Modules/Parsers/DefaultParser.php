<?php

namespace App\Modules\Parsers;

class DefaultParser extends ParserKernel implements ParserInterface
{
    protected array $config = [];

    public function __construct($link, $config = [])
    {
        $this->config = $config;
        parent::__construct($link);
    }

    public function getProductName():string
    {
        return isset($this->config['name_selects'])
            ? $this->getText($this->config['name_selects'])
            : '';
    }

    public function getProductPrice():string
    {
        return isset($this->config['price_selects'])
            ? $this->getPrice($this->config['price_selects'])
            : '';
    }

    public function getProductCurrency():string
    {
        return isset($this->config['price_selects'])
            ? $this->getCurrency($this->config['price_selects'])
            : '';
    }

    public function getProductImage():string
    {
        return isset($this->config['image_selects'])
            ? $this->getImage($this->config['image_selects'])
            : '';
    }

    public function getProductImages():array
    {
        return isset($this->config['images_selects'])
            ? $this->getImages($this->config['images_selects'], $this->config['images_handlers'] ?? [])
            : [];
    }
}
