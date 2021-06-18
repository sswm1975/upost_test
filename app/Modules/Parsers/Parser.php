<?php

namespace App\Modules\Parsers;

use Illuminate\Support\Str;

class Parser
{
    protected $class;
    protected $link;

    protected static $parsers = [
        'www.ebay.com'   => \App\Modules\Parsers\Ebay::class,
        'rozetka.com.ua' => \App\Modules\Parsers\Rozetka::class,
        'aliexpress'     => \App\Modules\Parsers\Aliexpress::class,
        'alibaba.com'    => \App\Modules\Parsers\Alibaba::class,
    ];

    public function __construct($link)
    {
        $this->link = mb_strtolower($link);

        $class = null;
        foreach (static::$parsers as $url => $parser) {
            if (Str::contains($this->link, $url)) {
                $class = $parser;
            }
        }
        if (is_null($class) || !in_array('App\Modules\Parsers\ParserInterface', class_implements($class))) {
            return 'Parser class error';
        }

        $this->class = $class;
    }

    public function parser()
    {
        return new $this->class($this->link);
    }
}

