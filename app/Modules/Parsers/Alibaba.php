<?php

namespace App\Modules\Parsers;

class Alibaba implements ParserInterface
{
    protected string $link;
    protected array $data;

    public function __construct($link)
    {
        $this->link = $link;

        $content = file_get_contents($link);

        preg_match('/window.detailData = ({.+})/', $content, $matches);

        $this->data = json_decode($matches[1], true);
    }

    public function getProductName():string
    {
        return $this->data['globalData']['product']['subject'] ?? '';
    }

    public function getProductCategory():string
    {
        return end($this->data['globalData']['seo']['breadCrumb']['pathList'])['hrefObject']['name'] ?? '';
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

    public function getProductSize():string
    {
        return $this->data['globalData']['trade']['logisticInfo']['unitSize'] ?? '';
    }

    public function getProductWeight():string
    {
        return $this->data['globalData']['trade']['logisticInfo']['unitWeight'] ?? '';
    }

    public function getFavicon(): string
    {
        $domain = parse_url($this->link);

        return 'https://www.google.com/s2/favicons?domain=' . $domain['scheme'] . '://' . $domain['host'];
    }

    private function getImageToBase64($href):string
    {
        $type = pathinfo($href, PATHINFO_EXTENSION);
        $data = file_get_contents($href);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
