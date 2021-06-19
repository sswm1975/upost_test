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

    public function getProductCategory():string
    {
        return isset($this->config['category_selects'])
            ? $this->getText($this->config['category_selects'])
            : '';
    }

    public function getProductPrice():string
    {
        return isset($this->config['price_selects'])
            ? $this->getPrice($this->config['price_selects'])
            : '';
    }

    public function getProductImage():string
    {
        return isset($this->config['image_selects'])
            ? $this->getImage($this->config['image_selects'])
            : '';
    }

    public function getFavicon():string
    {
        return parent::getFavicon();
    }
}
