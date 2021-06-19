<?php

namespace App\Modules\Parsers;

use DOMDocument;
use DOMXPath;

class ParserKernel
{
    protected $domain;
    protected $xpath;

    public function __construct($link)
    {
        $this->domain = parse_url($link);

        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->strictErrorChecking = false;
        $doc->recover = true;

        libxml_use_internal_errors(true);
        $doc->loadHTMLFile($link);
        libxml_use_internal_errors(false);

        $this->xpath = new DOMXPath($doc);
    }

    public function findOne(string $select): string
    {
        if (empty($select)) return '';

        $find = $this->xpath->query($select);

        if (!$find->length) return '';

        return trim($find->item(0)->textContent);
    }

    public function find(array $selects): string
    {
        $found = '';
        foreach ($selects as $select) {
            if ($found = $this->findOne($select)) break;
        }

        return $found;
    }

    public function getText(array $selects): string
    {
        return utf8_decode(addslashes($this->find($selects)));
    }

    public function getPrice(array $selects): string
    {
        $found = $this->find($selects);

        if (!$found) return '';

        $currency = preg_replace('/[0-9.,]/', '', $found);
        $price = preg_replace('/[^0-9.,]/', '', $found);

        return $price . ' ' . $currency;
    }

    public function getImage(array $selects): string
    {
        $href = $this->find($selects);

        if (!$href) return '';

        return $this->getImageToBase64($href);
    }

    public function getFavicon(): string
    {
        $scheme = $this->domain['scheme'];
        $host = $this->domain['host'];

        return 'https://www.google.com/s2/favicons?domain=' . $scheme . '://' . $host;
    }

    public function getJsonDecode(array $selects):array
    {
        $json = $this->getText($selects);

        return $json ? json_decode(stripcslashes($json), true) : [];
    }

    public function getImageToBase64($href):string
    {
        $type = pathinfo($href, PATHINFO_EXTENSION);
        $data = file_get_contents($href);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
