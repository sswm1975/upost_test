<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Log;

class ApiRequestLogging
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Logging request.
     *
     * @param Request $request
     * @param JsonResponse $response
     */
    public function terminate(Request $request, JsonResponse $response)
    {
        if (!config('api_request_logging_enabled')) {
            return;
        }

        $endTime = microtime(true);
        $log = new Log();
        $log->time = $log->freshTimestamp();
        $log->duration = $endTime - LARAVEL_START;
        $log->duration_request = $endTime - $request->server('REQUEST_TIME_FLOAT');
        $log->ip = $request->ip();
        $log->url = $request->fullUrl();
        $log->method = $request->method();
        $log->input = $request->toArray();
        $log->server = $request->server();
        $log->save();
    }
}
