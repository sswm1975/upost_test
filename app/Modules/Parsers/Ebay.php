<?php

namespace App\Modules\Parsers;

class Ebay extends ParserKernel implements ParserInterface
{
    const NAME_SELECTS = [
        "//h1[@id='itemTitle']/span[1]/following-sibling::text()[1]",
        "//h1[@class='product-title']",
    ];

    const CATEGORY_SELECTS = [
        "//ol/li[5]",
        "//ul[@role='list']/li[1]",
    ];

    const PRICE_SELECTS = [
        "//div[@class='display-price']",
        "//span[@id='prcIsum']",
        "//span[@id='priceblock_saleprice']",
    ];

    const IMAGE_SELECTS = [
        "//img[@class='app-filmstrip__image cc-image']/@src",
        "//img[@id='icImg']/@src",
    ];

    public function getProductName():string
    {
        return $this->getText(static::NAME_SELECTS);
    }

    public function getProductCategory():string
    {
        return $this->getText(static::CATEGORY_SELECTS);
    }

    public function getProductPrice():string
    {
        return $this->getPrice(static::PRICE_SELECTS);
    }

    public function getProductImage():string
    {
        return $this->getImage(static::IMAGE_SELECTS);
    }

    public function getFavicon():string
    {
        return parent::getFavicon();
    }
}
