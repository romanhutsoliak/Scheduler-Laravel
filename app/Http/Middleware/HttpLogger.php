<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SocketQueue\SocketQueue;
use App\Services\HttpLoggerWebSocket;
use App\Events\HttpLogEvent;

class HttpLogger
{
    public function handle(Request $request, Closure $next)
    {
        // php processing time
        global $httpLoggerCache;
        $key = 'hl_' . md5($request->getPathInfo() . print_r($request->all(), true));
        $httpLoggerCache[$key] = microtime(true);

        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (!config('app.debug') || !env('SOCKET_HTTP_LOGGER')) return;

        $method = strtoupper($request->getMethod());
        if ($method == 'OPTIONS') return;

        $uri = $request->getPathInfo();

        // don't log it at all
        if (
            $uri == '/123'
        ) return;


        if ($response instanceof JsonResponse) $response_content = $response->content();
        else $response_content = $response->getContent();

        //Log::info("{$method} {$uri} - Request: ".json_encode($request->all())." - Response: {$response_content} - Files: ".implode(', ', $files));

        global $httpLoggerCache;
        $key = 'hl_' . md5($request->getPathInfo() . print_r($request->all(), true));
        $startTime = $httpLoggerCache[$key] ?? microtime(true);

        $headers = $request->headers->all();
        foreach ($headers as &$header) {
            if (is_array($header) && count($header) == 1) $header = $header[0];
        }

        $request_data['data'] = $request->all();
        if (strpos($response_content, '{') === 0 || strpos($response_content, '[') === 0) $response_data = json_decode($response_content, true);
        elseif (!empty($response_content)) $response_data = ['!_string_response_!' => htmlspecialchars($response_content)];
        else $response_data = '';

        if (isset($headers['php-auth-user'])) unset($headers['php-auth-user']);
        if (isset($headers['php-auth-pw'])) unset($headers['php-auth-pw']);

        $payload = [
            'userId' => auth()->user()->id ?? null,
            'method' => $method,
            'uri' => $uri . ($request->getQueryString() ? '?' . $request->getQueryString() : ''),
            'code' => $response->status(),
            'request' => $request_data,
            'response' => $response_data,
            'headers' => ['headers' => $headers],
            'phpProcessTime' => (string)(int)((microtime(true) - $startTime) * 1000),
            'isSupport' => false,
        ];

        if (strpos($uri, '/auth-admin/') === 0) {
            if (auth()->user() && auth()->user()->roles->where('name', 'Support team')->count()) $payload['isSupport'] = true;
        }

        try {
            event(new HttpLogEvent($payload));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug('HttpLogger: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::debug('payload: ' . print_r($payload, true));
            \Illuminate\Support\Facades\Log::debug('request_all: ' . print_r($request->all(), true));
        }
    }
}
