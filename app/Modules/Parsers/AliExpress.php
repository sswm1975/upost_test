<?php

namespace App\Modules\Parsers;

class AliExpress implements ParserInterface
{
    protected array $data;

    public function __construct($link)
    {
        $content = file_get_contents($link);

        preg_match('/data: ({.+})/', $content, $matches);

        $this->data = json_decode($matches[1], true);
    }

    public function getProductName():string
    {
        return $this->data['pageModule']['title'] ?? '';
    }

    public function getProductCategory():string
    {
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
        return $this->getImageToBase64($this->data['pageModule']['imagePath']);
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
