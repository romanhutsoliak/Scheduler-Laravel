<?php

namespace App\Traits;

use App\Events\HttpLogEvent;

trait ApiRequest
{

    // if need override this function to provide autorication header
    protected function makeAuthorizationToken($param)
    {
        return null;
    }

    public function sendGet(string $action, array $params = [], array $headers = [])
    {
        return $this->sendCommonStream($action, $params, $headers, 'GET');
    }

    public function sendPost(string $action, array $params = [], array $headers = [])
    {
        return $this->sendCommonStream($action, $params, $headers, 'POST');
    }

    protected function sendCommonStream(string $action, array $params = [], array $headers = [], $method = 'GET')
    {
        $startTime = microtime(true);

        if (strpos($action, 'http') === 0)
            $url = $action;
        else
            $url = $this->api_url . $action;

        $hasContentType = false;
        $hasAccept = false;
        $hasAuthorization = false;
        $hasAgent = false;
        foreach ($headers as $h) {
            if (strpos($h, 'Content-Type:') === 0)
                $hasContentType = true;
            if (strpos($h, 'Accept:') === 0)
                $hasAccept = true;
            if (strpos($h, 'Authorization:') === 0)
                $hasAuthorization = true;
            if (strpos($h, 'User-Agent:') === 0)
                $hasAgent = true;
        }
        if (!$hasContentType)
            $headers[] = 'Content-Type: application/json';
        if (!$hasAccept)
            $headers[] = 'Accept: application/json';
        if (!$hasAgent)
            $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0";

        $data_string = null;
        if (!empty($params)) {
            $encodeData = false;
            foreach ($headers as $h) {
                if (strpos($h, 'Content-Type: application/json') === 0)
                    $encodeData = true;
            }
            if ($encodeData)
                $data_string = json_encode($params);
            else
                $data_string = http_build_query($params, '', '&');
            $headers[] = "Content-Length: " . strlen($data_string);
        }

        if (!$hasAuthorization) {
            $authorizationToken = $this->makeAuthorizationToken((string) strlen($data_string ?? ''));
            if ($authorizationToken)
                $headers[] = 'Authorization: Basic ' . $authorizationToken;
        }

        // Create the context for the request
        $context = stream_context_create(array(
            'http' => [
                'method' => $method,
                'header' => $headers,
                'content' => $data_string,
                'protocol_version' => '1.1',
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ));
        // Send the request
        $responseRaw = @\file_get_contents($url, false, $context);

        $decodeData = false;
        foreach ($headers as $h) {
            if (strpos($h, 'Accept: application/json') === 0)
                $decodeData = true;
        }
        if ($decodeData)
            $response = json_decode($responseRaw, true);
        else
            $response = $responseRaw;

        $code = '404';
        if (!empty($http_response_header)) {
            preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
            $code = $match[1];
        }
        if (!empty($response['code']))
            $code = $response['code'];

        //\Illuminate\Support\Facades\Log::debug(date('Y-m-d H:i:s', time()).' - sendPostStream: ' .print_r($response, true));

        if (!isset($params['data']))
            $params = ['data' => $params];
        $responseLog = $response ?: $responseRaw;
        if (!isset($responseLog['data']))
            $responseLog = ['data' => $responseLog];

        if ($code != 200 && (int) $code > 200 && !isset($response['status']))
            $response['status'] = $code;


        $payload = [
            'userId' => auth()->user()->id ?? null,
            'method' => $method,
            'uri' => $url,
            'code' => $code,
            'request' => $params,
            'response' => $response,
            'headers' => ['headers' => $headers],
            'phpProcessTime' => (string)(int)((microtime(true) - $startTime) * 1000),
            'isSupport' => false,
        ];

        try {
            event(new HttpLogEvent($payload));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug('ApiRequest HttpLogger: ' . $e->getMessage());
        }

        return $response;
    }

    public function sendCurl(string $action, array $params = [], array $headers = [], $method = 'POST', $return_with_headers = false)
    {

        if (strpos($action, 'http') === 0) $url = $action;
        else $url = $this->api_url . $action;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $hasContentType = false;
        $hasAccept = false;
        foreach ($headers as $h) {
            if (strpos($h, 'Content-Type:') === 0) $hasContentType = true;
            if (strpos($h, 'Accept:') === 0) $hasAccept = true;
        }
        if (!$hasContentType) $headers[] = "Content-Type: application/json";
        if (!$hasAccept) $headers[] = "Accept: application/json, text/plain, */*";

        $encodeData = false;
        foreach ($headers as $h) {
            if (strpos($h, 'Content-Type: application/json') === 0) $encodeData = true;
        }
        if ($encodeData) $data_string = json_encode($params);
        else $data_string = http_build_query($params, '', '&');

        $headers[] = "Content-Length: " . strlen($data_string);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($params)) curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string); //json_encode //http_build_query($params, '', '&')
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // переходить по редиректам
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // переходить по редиректам
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5); //Максимальное количество постоянных соединений
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // возвращать а не выводить ответ
        if ($return_with_headers) {
            curl_setopt($ch, CURLOPT_HEADER, true); // выводить заголовки
            curl_setopt($ch, CURLOPT_VERBOSE, true); // выводит сведения о сертификатах
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $server_output = curl_exec($ch);

        if ($return_with_headers) {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($server_output, 0, $header_size);
            $body = substr($server_output, $header_size);

            curl_close($ch);
            return [
                'all' => $server_output,
                'header' => $header,
                'body' => $body,
            ];
        }
        curl_close($ch);

        return $server_output;
    }
}
