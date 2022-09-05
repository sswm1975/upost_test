<?php

namespace App\Libs;

use Illuminate\Support\Facades\DB;

class TestHelpers
{
    /**
     * Сбрасывание счетчика неудачных попыток для middleware('throttle:5,10') и локального IP 127.0.0.1
     *
     * @return void
     */
    public static function clearLoginAttempts()
    {
        # см. как определяется ключ в методе resolveRequestSignature класса Illuminate\Routing\Middleware\ThrottleRequests
        $ip = request()->ip();
        $domain = optional(request()->route())->getDomain();
        $key = sha1("{$domain}|{$ip}");

        # подключаемся к классу RateLimiter, который может сбрасывать счетчик
        app(\Illuminate\Cache\RateLimiter::class)->clear($key);
    }
}
