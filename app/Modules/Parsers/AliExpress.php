<?php

namespace App\Modules\Parsers;

class AliExpress implements ParserInterface
{
    protected string $link;
    protected array $data;

    public function __construct($link)
    {
        $this->link = $link;

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
        $price = $this->data['priceModule']['minActivityAmount']['value'] ?? '';
        $currency =  $this->data['priceModule']['minActivityAmount']['currency'] ?? '';

        return $price . ' ' . $currency;
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
