<?php

namespace App\Modules\Parsers;

use Illuminate\Support\Str;

class Parser
{
    protected $handler = null;
    protected array $config = [];
    protected string $link;

    public function __construct($link)
    {
        $this->link = mb_strtolower($link);
        $parsers = config('parser.parsers');

        foreach ($parsers as $parser) {
            if (isset($parser['mask']) && Str::contains($this->link, $parser['mask'])) {
                $this->handler = $parser['handler'] ?? null;
                $this->config = $parser;
            }
        }

        if (empty($this->handler) || !in_array('App\Modules\Parsers\ParserInterface', class_implements($this->handler))) {
            return 'Parser class error';
        }
    }

    public function handler()
    {
        return new $this->handler($this->link, $this->config);
    }
}

