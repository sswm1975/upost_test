<?php

namespace App\Modules\Parsers;

use DOMDocument;
use DOMXPath;

class ParserKernel
{
    const CLEAR_CHARS = " \t\n\r\0\x0B\xC2\xA0";

    protected DOMXPath $xpath;

    public function __construct($link)
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->strictErrorChecking = false;
        $doc->recover = true;

        libxml_use_internal_errors(true);
        $doc->loadHTMLFile($link);
        libxml_use_internal_errors(false);

        $this->xpath = new DOMXPath($doc);
    }

    function cleanTags($string): string
    {
        return trim(preg_replace('/(\x{200e}|\x{200f})/u', '', strip_tags($string)), self::CLEAR_CHARS);
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
        return $this->cleanTags(addslashes($this->find($selects) ));
    }

    public function getPrice(array $selects): string
    {
        $found = $this->find($selects);

        if (!$found) return '';

        return trim(preg_replace('/[^0-9.,]/', '', $found), " \t\n\r\0\x0B\xC2\xA0");
    }

    public function getCurrency(array $selects): string
    {
        $found = $this->find($selects);

        if (!$found) return '';

        $currency =  trim(preg_replace('/[0-9.,]/', '', $found), " \t\n\r\0\x0B\xC2\xA0");

        return getCurrencySymbol($currency);
    }

    public function getImage(array $selects): string
    {
        $href = $this->find($selects);

        if (!$href) return '';

        return $this->getImageToBase64($href);
    }

    public function getJsonDecode(array $selects):array
    {
        $json = $this->find($selects);

        return $json ? json_decode(utf8_decode($json), true) : [];
    }

    public function getImageToBase64($href):string
    {
        $type = pathinfo($href, PATHINFO_EXTENSION);
        $data = file_get_contents($href);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
