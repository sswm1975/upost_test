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

    public function getProductCategory():string
    {
        if (empty($this->data['crossLinkModule']['breadCrumbPathList'])) {
            return '';
        }

        return end($this->data['crossLinkModule']['breadCrumbPathList'])['name'] ?? '';
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

        return $this->getImageToBase64($this->data['pageModule']['imagePath']);
    }

    public function getProductImages():array
    {
        return [];
    }

    public function getProductSize():string
    {
        return '';
    }

    public function getProductWeight():string
    {
        return '';
    }

    private function getImageToBase64($href):string
    {
        $type = pathinfo($href, PATHINFO_EXTENSION);
        $data = file_get_contents($href);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
