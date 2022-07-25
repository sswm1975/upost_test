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
        # убираем из ссылки параметры и переводим в нижний регистр
        $this->link = mb_strtolower(strtok($link, '?'));

        $parsers = config('parser.parsers');

        foreach ($parsers as $parser) {
            if (isset($parser['mask']) && Str::contains($this->link, $parser['mask'])) {
                $this->handler = $parser['handler'] ?? null;
                $this->config = $parser;
                break;
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

