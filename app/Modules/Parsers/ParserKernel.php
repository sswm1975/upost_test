<?php

namespace App\Modules\Parsers;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

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

        try {
            libxml_use_internal_errors(true);
            $doc->loadHTMLFile($link);
            libxml_use_internal_errors(false);
        } catch (\Exception $e) {
            $doc->createElement("root");
        }

        $this->xpath = new DOMXPath($doc);
    }

    function cleanTags($string): string
    {
        return trim(preg_replace('/(\x{200e}|\x{200f})/u', '', strip_tags($string)), self::CLEAR_CHARS);
    }

    /**
     * Найти первое вхождение.
     *
     * @param array $selects  Массив XPath-селекторов.
     * @return string
     */
    public function findFirst(array $selects): string
    {
        if (empty($selects)) return '';

        $found = '';
        foreach ($selects as $select) {
            $find = $this->xpath->query($select);

            if ($find->length) {
                $found = trim($find->item(0)->textContent);
                break;
            }
        }

        return $found;
    }

    /**
     * Найти все вхождения.
     *
     * @param array $selects  Массив XPath-селекторов.
     * @return array
     */
    public function findAll(array $selects): array
    {
        if (empty($selects)) return [];

        $items = [];
        foreach ($selects as $select) {
            $find = $this->xpath->query($select);

            if (!$find->length) continue;

            for ($i = 0; $i < $find->length; $i++) {
                $items[] = trim($find->item($i)->textContent);
            }
        }

        return $items;
    }

    public function getText(array $selects): string
    {
        return $this->cleanTags(addslashes($this->findFirst($selects)));
    }

    public function getPrice(array $selects): string
    {
        $found = $this->findFirst($selects);

        if (!$found) return '';

        return trim(preg_replace('/[^0-9.,]/', '', $found), " \t\n\r\0\x0B\xC2\xA0");
    }

    public function getCurrency(array $selects): string
    {
        $found = $this->findFirst($selects);

        if (!$found) return '';

        $currency =  trim(preg_replace('/[0-9.,]/', '', $found), " \t\n\r\0\x0B\xC2\xA0");

        return getCurrencySymbol($currency);
    }

    /**
     * Получить основное фото товара.
     *
     * @param array $selects   Массив XPath-селекторов.
     * @return string          Рисунок в формате base64.
     */
    public function getImage(array $selects): string
    {
        $href = $this->findFirst($selects);

        if (!$href) return '';

        return $this->getImageToBase64($href);
    }

    /**
     * Получить все рисунки по списку XPath-селекторов.
     *
     * @param array $selects      Массив XPath-селекторов.
     * @param array $handlers     Обработчики, через которые нужно пропустить список ссылок с рисунками.
     * @return array              Массив рисунков в формате base64.
     */
    public function getImages(array $selects, array $handlers = []): array
    {
        $urls = $this->findAll($selects);

        if (empty($urls)) return [];

        $urls = $this->handlerImages($handlers, $urls);

        $images = [];
        foreach ($urls as $url) {
            $images[] = $this->getImageToBase64($url);
        }

        return $images;
    }

    public function getJsonDecode(array $selects):array
    {
        $json = $this->findFirst($selects);

        return $json ? json_decode(utf8_decode($json), true) : [];
    }

    public function getImageToBase64($href):string
    {
        $type = pathinfo($href, PATHINFO_EXTENSION);
        $data = file_get_contents($href);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    /**
     * Обработчик ссылок с рисунками.
     * Поддерживает функции:
     *   replace:search,replace - заменить все вхождения search на replace.
     *
     * @param array $handlers  Список обработчиков
     * @param array $urls      Список ссылок с рисунками
     * @return array           Возвращается не больше 8 ссылок с рисунками
     */
    private function handlerImages(array $handlers, array $urls):array
    {
        foreach ($handlers as $handler) {
            if (Str::startsWith($handler, 'replace:')) {
                $params = Str::replaceFirst('replace:', '', $handler);
                $params = explode(',', $params);
                foreach ($urls as $key => $url) {
                    $urls[$key] = str_replace($params[0], $params[1], $url);
                }
            }
        }

        return array_slice($urls, 0, 8);
    }
}
