<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Максимальное количество возвращаемых изображений товара
    |--------------------------------------------------------------------------
    |
    */
    'max_images_count' => 3,

    /*
    |--------------------------------------------------------------------------
    | Настройки для парсера интернет-магазинов
    |--------------------------------------------------------------------------
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
            'price_selects' => [
                "//div[@class='display-price']",
                "//span[@id='prcIsum']",
                "//span[@id='priceblock_saleprice']",
                "//div[@class='x-price-primary']/span",
            ],
            'image_selects' => [
                "//img[@class='app-filmstrip__image cc-image']/@src",
                "//img[@id='icImg']/@src",
                "//img[@class='vi-image-gallery__image vi-image-gallery__image--absolute-center']/@src"
            ],
            'images_selects' => [
                "//img[@data-index]/@src",
                "//img[@width='64']/@data-originalimg",
                "//button[@class='ux-image-filmstrip-carousel-item image']/img/@src",
                "//button[@data-idx]/img/@src",
                "//button/img[@loading]/@src",
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
        ],

        'moyo' => [
            'mask' => 'moyo.ua',
            'handler' => \App\Modules\Parsers\DefaultParser::class,
            'name_selects' => [
                "//h1[@class='tovar_title__name']",
                "//h1[@class='product_name']",
            ],
            'price_selects' => [
                "//div[@class='actual-price' or @id='priceblock_saleprice']",
                "//div[contains(@class,'product_price_current')]",
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
            'price_selects' => [
                '//div[@class="priceView-hero-price priceView-customer-price"]/span',
            ],
            'image_selects' => [
                '//img[@class="primary-image"]/@src',
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
            'price_selects' => [
                '//div[@class="_30jeq3 _16Jk6d"]',
            ],
            'image_selects' => [
                '//div[@class="_3kidJX"]/*/img/@src',
            ],
            'images_selects' => [
                '//img[@class="q6DClP"]/@src',
            ],
        ],
    ],
];
