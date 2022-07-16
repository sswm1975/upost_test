<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Log;
use Illuminate\Support\Facades\DB;

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
        if (config('api_request_logging_enabled', 0)) {
            DB::enableQueryLog();
        }

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
        if (! config('api_request_logging_enabled', 0)) {
            return;
        }

        $endTime = microtime(true);
        $log = new Log();
        $log->time = $log->freshTimestamp();
        $log->duration = $endTime - LARAVEL_START;
        $log->duration_request = $endTime - $request->server('REQUEST_TIME_FLOAT');
        $log->server_ip = $request->ip();
        $log->client_ip = $request->server('HTTP_WP_CLIENT_IP');
        $log->prefix = $request->segment(2);
        $log->url = $request->fullUrl();
        $log->method = $request->method();
        $log->input = $request->toArray();
        $log->output = json_decode($response->content());
        $log->server = $request->server();
        $log->queries = getSQLForFixDatabase();
        $log->save();
    }
}
