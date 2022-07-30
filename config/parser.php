<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Настройки для парсера интернет-магазинов
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
            'images_handlers' => [
                'replace:s-l64.,s-l500.',
            ],
            'name_selects' => [
                "//h1[@id='itemTitle']/span[1]/following-sibling::text()[1]",
                "//h1[@class='product-title']",
                "//h1",
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
                "//img[@class='vi-image-gallery__image vi-image-gallery__image--absolute-center']/@src"
            ],
            'images_selects' => [
                "//img[@data-index]/@src",
                "//img[@width='64']/@data-originalimg",
            ],
            'size_selects' => [

            ],
            'weight_selects' => [

            ],
        ],

        'rozetka' => [
            'mask' => 'rozetka.com.ua',
            'handler' => \App\Modules\Parsers\Rozetka::class,
            'images_handlers' => [
                'replace:base_action,original',
            ],
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
            'images_handlers' => [
                'reg_exp:/"hiRes":"(\S+?)"/m',
            ],
            'name_selects' => [
                "//h1[@class='a-size-large a-spacing-none']",
            ],
            'category_selects' => [
                "//div[@id='wayfinding-breadcrumbs_feature_div']/ul/li[1]",
            ],
            'price_selects' => [
                "//span[@class='a-offscreen']",
                "//span[@class='a-size-mini olpWrapper']",
                "//span[@id='priceblock_ourprice']",
                "//span[@id='priceblock_saleprice']",
                "//p[@class='a-spacing-none a-text-left a-size-mini twisterSwatchPrice']",
            ],
            'image_selects' => [
                "//img[@id='landingImage']/@src",
            ],
            'images_selects' => [
                "//script[contains(text(), 'ImageBlockATF')]/text()",
            ],
            'size_selects' => [
                "//th[contains(text(),'Product Dimensions')]/following-sibling::td[1]",
            ],
            'weight_selects' => [
                "//th[contains(text(),'Item Weight')]/following-sibling::td[1]",
            ],
        ],

        'moyo' => [
            'mask' => 'moyo.ua',
            'handler' => \App\Modules\Parsers\DefaultParser::class,
            'name_selects' => [
                "//h1[@class='tovar_title__name']",
                "//h1[@class='product_name']",
            ],
            'category_selects' => [
                "//div[@id='breadcrumbs']/ul/li[2]",
                "//ol[@class='breadcrumbs']/li[2]",
            ],
            'price_selects' => [
                "//div[@class='actual-price' or @id='priceblock_saleprice']",
                "//div[@class='product_price_current sale js-current-price']",
            ],
            'image_selects' => [
                "//img[@class='fits']/@src",
                "//img[@class='lazy-interaction']/@src",
            ],
            'images_selects' => [
                "//img[@class='lazy-interaction']/@srcset", # средний размер
                "//img[@class='lazy-interaction']/@src",    # большой размер
                "//img[@class='fits']/@src",
            ],
            'size_selects' => [

            ],
            'weight_selects' => [

            ],
        ],

        'aliexpress' => [
            'mask' => 'aliexpress',
            'handler' => \App\Modules\Parsers\AliExpress::class,
        ],

        'alibaba' => [
            'mask' => 'alibaba.com',
            'handler' => \App\Modules\Parsers\Alibaba::class,
        ],

        'bestbuy' => [
            'mask' => 'bestbuy.com',
            'handler' => \App\Modules\Parsers\BestBuy::class,
            'name_selects' => [
                '//h1[@class]',
            ],
            'category_selects' => [
                '//nav/ol/li[2]',
            ],
            'price_selects' => [
                '//div[@class="priceView-hero-price priceView-customer-price"]/span',
            ],
            'image_selects' => [
                '//img[@class="primary-image"]/@src',
            ],
            'size_selects' => [

            ],
            'weight_selects' => [

            ],
        ],

        'flipkart' => [
            'mask' => 'flipkart.com',
            'handler' => \App\Modules\Parsers\DefaultParser::class,
            'images_handlers' => [
                'replace:128/128,832/832',
            ],
            'name_selects' => [
                '//h1/span',
            ],
            'category_selects' => [
                '//div[@class="_3GIHBu"][4]',
            ],
            'price_selects' => [
                '//div[@class="_30jeq3 _16Jk6d"]',
            ],
            'image_selects' => [
                '//div[@class="_3kidJX"]/*/img/@src',
            ],
            'images_selects' => [
                '//img[@class="q6DClP"]/@src',
            ],
            'size_selects' => [

            ],
            'weight_selects' => [

            ],
        ],
    ],
];
