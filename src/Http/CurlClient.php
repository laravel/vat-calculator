<?php

namespace Mpociot\VatCalculator\Http;

class CurlClient
{
    /**
     * Send a GET request.
     *
     * @throws \RuntimeException on cURL error
     */
    public function get(string $url, array $headers = []): string
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \RuntimeException('cURL GET error: '.curl_error($ch));
        }

        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        return $response;
    }

    /**
     * Send a POST request with JSON body.
     *
     * @param  array|string  $data
     *
     * @throws \RuntimeException on cURL error
     */
    public function post(string $url, array $headers = [], $data = [], bool $json = true): string
    {
        $ch = curl_init($url);

        $postFields = $json ? json_encode($data) : (is_array($data) ? http_build_query($data) : $data);

        if ($json) {
            $headers = array_merge(['Content-Type: application/json'], $headers);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \RuntimeException('cURL POST error: '.curl_error($ch));
        }

        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        return $response;
    }

    /**
     * Send a GET request and return response with HTTP status code and headers.
     *
     * @param  string  $url  The URL to send the GET request to
     * @param  array  $headers  Optional array of HTTP headers to include in the request
     * @return array Associative array containing statusCode, headers, and body
     *
     * @throws \RuntimeException on cURL error
     */
    public function getWithStatus(string $url, array $headers = []): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true,  // We want headers in output
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \RuntimeException('cURL GET error: '.curl_error($ch));
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'statusCode' => $statusCode,
            'headers' => $header,
            'body' => $body,
        ];
    }
}
