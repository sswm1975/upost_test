<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     * @param $response
     */
    public function terminate(Request $request, $response)
    {
        if (! config('api_request_logging_enabled', 0)) {
            return;
        }
        $output = null;

        if ($response instanceof JsonResponse) {
            $output = json_decode($response->content());
        }

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            \Log::notice(json_decode($response->getRequest()->getContent()));
            $output = json_decode($response->getRequest()->getContent());
        }

        $endTime = microtime(true);
        $log = new Log();
        $log->time = $log->freshTimestamp();
        $log->duration = $endTime - LARAVEL_START;
        $log->duration_request = $endTime - $request->server('REQUEST_TIME_FLOAT');
        $log->server_ip = $request->ip();
        $log->client_ip = $request->server('HTTP_WP_CLIENT_IP');
        $log->prefix = $request->segment(2);
        $log->url = Str::substr($request->fullUrl(), 0,1000);
        $log->method = $request->method();
        $log->input = $request->toArray();
        $log->output = $output;
        $log->server = $request->server();
        $log->queries = getSQLForFixDatabase();
        $log->save();
    }
}
