<?php

namespace App\Modules\Parsers;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class ParserKernel
{
    # Cпецсимволы: табуляция, перевод строки, возврат каретки, NULL-байты, вертикальная табуляция, неразрывный пробел
    const CLEAR_CHARS = " \t\n\r\0\x0B\xC2\xA0";

    protected DOMXPath $xpath;

    /**
     * Конструктор.
     *
     * @param $link
     */
    public function __construct($link)
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->strictErrorChecking = false;
        $doc->recover = true;

        try {
            libxml_use_internal_errors(true);
            $doc->loadHTMLFile($link, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_use_internal_errors(false);
        } catch (\Exception $e) {
            $doc->createElement("root");
        }

        $this->xpath = new DOMXPath($doc);
    }

    /**
     * Удаление тегов и различных спец.симолов.
     *   \x{200e}|\x{200f} - trim unicode direction mark (LEFT-TO-RIGHT-MARK and RIGHT-TO-LEFT-MARK);
     *   strip_tags - удаляет все NULL-байты, HTML- и PHP-теги
     *   self::CLEAR_CHARS - спецсимвол: табуляция, перевод строки, возврат каретки, NULL-байты, вертикальная табуляция, неразрывный пробел
     *
     * @param $string
     * @return string
     */
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

        preg_match('/[\$\£\€](\d+(?:[\.,]\d{1,2})?)/', $found, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }

        return trim(preg_replace('/[^0-9.,]/', '', $found), self::CLEAR_CHARS);
    }

    public function getCurrency(array $selects): string
    {
        $found = $this->findFirst($selects);

        if (!$found) return '';

        $currency =  trim(preg_replace('/[0-9.,]/', '', $found), self::CLEAR_CHARS);

        return getCurrencySymbol($currency);
    }

    /**
     * Получить основное фото товара по списку XPath-селекторов.
     *
     * @param array $selects   Массив XPath-селекторов.
     * @return string          Рисунок в формате base64.
     */
    public function getImage(array $selects): string
    {
        $url = $this->findFirst($selects);

        return $url ? convertImageToBase64($url) : '';
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
            $images[] = convertImageToBase64($url);
        }

        return $images;
    }

    /**
     * Обработчик ссылок с рисунками.
     * Поддерживает функции:
     *   replace:search,replace - заменить все вхождения search на replace;
     *   reg_exp:pattern - получить ссылки по патерну регулярного выражения.
     *
     * @param array $handlers  Список обработчиков
     * @param array $urls      Список ссылок с рисунками
     * @return array           Возвращается ссылки с рисунками
     */
    protected function handlerImages(array $handlers, array $urls):array
    {
        foreach ($handlers as $handler) {
            if (Str::startsWith($handler, 'replace:')) {
                $params = explode(',', str_replace('replace:', '', $handler));
                foreach ($urls as $key => $url) {
                    $urls[$key] = str_replace($params[0], $params[1], $url);
                }
            }
            if (Str::startsWith($handler, 'reg_exp:')) {
                $pattern = str_replace('reg_exp:', '', $handler);
                preg_match_all($pattern, $urls[0], $matches);
                $urls = $matches[1] ?? [];
            }
        }

        return $urls;
    }
}
