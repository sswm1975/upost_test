<?php

namespace App\Modules\Parsers;

use DOMDocument;
use DOMXPath;

class BestBuy extends DefaultParser
{
    protected array $config = [];

    public function __construct($link, $config = [])
    {
        $this->config = $config;

        $context = stream_context_create([
            'http'=>[
                'method' => "GET",
                'header' =>
                    "User-Agent: PostmanRuntime/7.28.4\r\n" .
                    "Accept: */*\r\n" .
                    "Host: www.bestbuy.com\r\n" .
                    "Accept-Encoding: gzip, deflate, br\r\n" .
                    "Connection: keep-alive\r\n" .
                    "Cookie: CTT=d9c68daa696e891225180dd9995d44a6; SID=2c8f0ffd-01bc-49fd-aaef-43edfee1482a; _abck=BA92478B804423092575719C3CC48854~-1~YAAQFtT1V6p3R+B6AQAABn9cjAZ2yHBtMWjFWqIIJa7jTX9SMDw4B4p5wR6fQ5uoYyivWRGiWmLVPGXr5+VPpYzj2bXnDm0nFze3m9+pw76sx+hLtnlj4RG1nKQXazL6sSh2ud7w+u9bIbg13mEHOLClmqXdt+16bmLim/SDWp80HZFERbkjs0MrE9vWw1So4HCRSG6SuXK8tYQBZJigJ/CzQlbnUo+ucH7Td5kNucCHPyvvnTh7mxu5KENczSs18MPbcSifitYYDtOElM5CPxBzqkhLOGA/g9AdeC6zyRglcuzX6aTIuUaaUCXaDgcS4i8nY/AiK7b6QgysDKcqP7fRDJxrwo2doueztagob0QT/fO1osB2r6NwdJZ5~-1~-1~-1; bby_cbc_lb=p-browse-e; bby_rdp=l; bm_sz=EF45ADC19BE41D9817F8F454DE228ECD~YAAQFtT1V6t3R+B6AQAABn9cjAzfeJhrTJSimAJIFq8S640YW0uRCvy3tHdSVbEm1LvpTJy/PzL6L/zrihz8MMggkedSmJ+LDOfvaVmnidTNJMVJ8Vry/oDxKBVZLXSmKmz1X6Kqnwx/skS7PEsJs6FvFx+LMK4gXYl/d9XUlMI6j/bMUxi/U4WsEjre6GCwb1M7x3Kci3hksGwCA5cu6AkVTJvbUk0I9IGGDdPdrIgxrOKlSKoXzSPZKO88kue58xI0ABY3dBKZm4icy1XOh1fvUnS0mXfJzZWKUPSZlZhtEXaCPTxuwiFsQJc5L14GTJeB1lDl9Dw/oz4YGsPugbEUYgcCvXPQmThcAlH+wYTPklCEKebkVT9TMzIVN/uNWRQ=~3294513~3227972; intl_splash=false; ltc=%20; oid=667285501; vt=f0d79895-07ec-11ec-b963-0a72d345912d\r\n"
            ]
        ]);
        $content = gzdecode(file_get_contents($link, false, $context));

        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->strictErrorChecking = false;
        $doc->recover = true;

        $use_errors = libxml_use_internal_errors(true);
        $doc->loadHTML($content, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_ERR_NONE | LIBXML_COMPACT);
        libxml_use_internal_errors($use_errors);

        $this->xpath = new DOMXPath($doc);
    }
}
