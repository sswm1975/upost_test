<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Настройки для парсер интернет-магазинов
    |--------------------------------------------------------------------------
    |
    |
    |
    |
    |
    */
    'parsers' => [
        'ebay' => [
            'mask' => 'www.ebay.com',
            'handler' => \App\Modules\Parsers\DefaultParser::class,
            'name_selects' => [
                "//h1[@id='itemTitle']/span[1]/following-sibling::text()[1]",
                "//h1[@class='product-title']",
            ],
            'category_selects' => [
                "//ol/li[5]",
                "//ul[@role='list']/li[1]",
            ],
            'price_selects' => [
                "//div[@class='display-price']",
                "//span[@id='prcIsum']",
                "//span[@id='priceblock_saleprice']",
            ],
            'image_selects' => [
                "//img[@class='app-filmstrip__image cc-image']/@src",
                "//img[@id='icImg']/@src",
            ],
            'size_selects' => [

            ],
            'weight_selects' => [

            ],
        ],

        'rozetka' => [
            'mask' => 'rozetka.com.ua',
            'handler' => \App\Modules\Parsers\Rozetka::class,
            'product' => [
                "//script[@data-seo='Product']",
            ],
            'category' => [
                "//script[@data-seo='BreadcrumbList']",
            ],
        ],

        'amazon' => [
            'mask' => 'amazon.com',
            'handler' => \App\Modules\Parsers\DefaultParser::class,
            'name_selects' => [
                "//h1[@class='a-size-large a-spacing-none']",
            ],
            'category_selects' => [
                "//div[@id='wayfinding-breadcrumbs_feature_div']/ul/li[1]",
            ],
            'price_selects' => [
                "//span[@id='priceblock_ourprice']",
                "//span[@id='priceblock_saleprice']",
                "//p[@class='a-spacing-none a-text-left a-size-mini twisterSwatchPrice']",
            ],
            'image_selects' => [
                "//img[@id='landingImage']/@src",
            ],
            'size_selects' => [
                "//th[contains(text(),'Product Dimensions')]/following-sibling::td[1]",
            ],
            'weight_selects' => [
                "//th[contains(text(),'Item Weight')]/following-sibling::td[1]",
            ],
        ],
    ],
];
