<?php

namespace App\Modules\Parsers;

class AliExpress implements ParserInterface
{
    /** @var array|null  */
    protected $data;

    public function __construct($link)
    {
        $content = @file_get_contents($link);

        preg_match('/data: ({.+})/', $content, $matches);

        $this->data = !empty($matches[1]) ? json_decode($matches[1], true) : null;
    }

    public function getProductName():string
    {
        return $this->data['pageModule']['title'] ?? '';
    }

    public function getProductPrice():string
    {
        return $this->data['priceModule']['minActivityAmount']['value'] ?? '';
    }

    public function getProductCurrency():string
    {
        return getCurrencySymbol($this->data['priceModule']['minActivityAmount']['currency'] ?? '');
    }

    public function getProductImage():string
    {
        if (empty($this->data['pageModule']['imagePath'])) {
            return '';
        }

        return convertImageToBase64($this->data['pageModule']['imagePath']);
    }

    public function getProductImages():array
    {
        if (empty($this->data['imageModule']['imagePathList'])) {
            return [];
        }

        $images = [];
        foreach ($this->data['imageModule']['imagePathList'] as $url) {
            $images[] = convertImageToBase64($url);
        }

        return $images;
    }
}
