<?php

namespace App\Modules\Parsers;

class Alibaba implements ParserInterface
{
    protected array $data;

    public function __construct($link)
    {
        $content = file_get_contents($link);

        preg_match('/window.detailData = ({.+})/', $content, $matches);

        $this->data = json_decode($matches[1], true);
    }

    public function getProductName():string
    {
        return $this->data['globalData']['product']['subject'] ?? '';
    }

    public function getProductPrice():string
    {
        $price_with_currency = $this->data['globalData']['product']['price']['productLadderPrices'][0]['formatPrice'] ?? '';

        return preg_replace('/[^0-9.,]/', '', $price_with_currency);
    }

    public function getProductCurrency():string
    {
        $price_with_currency = $this->data['globalData']['product']['price']['productLadderPrices'][0]['formatPrice'] ?? '';

        return getCurrencySymbol($price_with_currency);
    }

    public function getProductImage():string
    {
        $image = '';
        foreach ($this->data['globalData']['product']['mediaItems'] as $item) {
            if ($item['type'] == 'image' && !empty($item['imageUrl']['big'])) {
                $image = $item['imageUrl']['big'];
                break;
            }
        }

        return $image ? convertImageToBase64($image) : '';
    }

    public function getProductImages():array
    {
        if (empty($this->data['imageModule']['imagePathList'])) {
            return [];
        }

        $images = [];
        foreach ($this->data['globalData']['product']['mediaItems'] as $item) {
            if ($item['type'] == 'image' && isset($item['imageUrl']['big'])) {
                $images[] = convertImageToBase64($item['imageUrl']['big']);
            }
        }

        return $images;
    }
}
