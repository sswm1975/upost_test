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

        return $image ? $this->getImageToBase64($image) : '';
    }

    public function getProductImages():array
    {
        $urls = array_slice($this->data['globalData']['product']['mediaItems'], 0, self::MAX_IMAGES_COUNT);

        $images = [];
        foreach ($urls as $item) {
            if ($item['type'] == 'image' && isset($item['imageUrl']['big'])) {
                $images[] = $this->getImageToBase64($item['imageUrl']['big']);
            }
        }

        return $images;
    }

    private function getImageToBase64($href):string
    {
        $type = pathinfo($href, PATHINFO_EXTENSION);
        $data = file_get_contents($href);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
