<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ZoomJWT
{
    /**
     * Generate JWT token using environment variables for using Zoom API.
     *
     * @return string
     */
    private function generateZoomToken()
    {
        $key = env('ZOOM_API_KEY', '');
        $secret = env('ZOOM_API_SECRET', '');
        $payload = [
            'iss' => $key,
            'exp' => strtotime('+1 minute'),
        ];
        return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Retrieve Zoom API base url from environment variable.
     *
     * @return mixed
     */
    private function retrieveZoomUrl()
    {
        return env('ZOOM_API_URL', '');
    }

    /**
     * Generate Request with common headers.
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function zoomRequest()
    {
        $jwt = $this->generateZoomToken();
        return \Illuminate\Support\Facades\Http::withHeaders([
            'authorization' => 'Bearer ' . $jwt,
            'content-type' => 'application/json',
        ]);
    }

    /**
     * Generate Request using GuzzleHttp Library
     *
     * @param string $methods
     * @param string $path
     * @param array $query
     * @param array $body
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function zoomRequestForLaravel6(string $methods, string $path, array $query = [], array $body = [])
    {
        $url = $this->retrieveZoomUrl() . $path;

        $jwt = $this->generateZoomToken();
        $headers = [
            'authorization' => 'Bearer ' . $jwt,
            'content-type' => 'application/json',
        ];
        $options = [
            'headers' => $headers,
            'query' => $query,
            'json' => $body,
            'verify' => false,
        ];

        $client = new \GuzzleHttp\Client();
        return $client->request($methods, $url, $options);
    }

    /**
     * Generate GET request using path and query.
     *
     * @param string $path
     * @param array $query
     * @return \Illuminate\Http\Client\Response
     */
    public function zoomGet(string $path, array $query = [])
    {
        $url = $this->retrieveZoomUrl();
        $request = $this->zoomRequest();
        return $request->get($url . $path, $query);
    }

    /**
     * Generate POST request using path and request body.
     *
     * @param string $path
     * @param array $body
     * @return \Illuminate\Http\Client\Response
     */
    public function zoomPost(string $path, array $body = [])
    {
        $url = $this->retrieveZoomUrl();
        $request = $this->zoomRequest();
        return $request->post($url . $path, $body);
    }

    /**
     * Generate PATCH request using path and request body.
     *
     * @param string $path
     * @param array $body
     * @return \Illuminate\Http\Client\Response
     */
    public function zoomPatch(string $path, array $body = [])
    {
        $url = $this->retrieveZoomUrl();
        $request = $this->zoomRequest();
        return $request->patch($url . $path, $body);
    }

    /**
     * Generate DELETE request using path and request body.
     *
     * @param string $path
     * @param array $body
     * @return \Illuminate\Http\Client\Response
     */
    public function zoomDelete(string $path, array $body = [])
    {
        $url = $this->retrieveZoomUrl();
        $request = $this->zoomRequest();
        return $request->delete($url . $path, $body);
    }

    /**
     * Generate time string of Zoom format.
     *
     * @param string $dateTime
     * @return string
     */
    public function toZoomTimeFormat(string $dateTime)
    {
        try {
            $date = new \DateTime($dateTime);
            return $date->format('Y-m-d\TH:i:s');
        } catch(\Exception $e) {
            Log::error('ZoomJWT->toZoomTimeFormat : ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Generate unix timestamp using time string and timezone information.
     *
     * @param string $dateTime
     * @param string $timezone
     * @return int|null
     */
    public function toUnixTimeStamp(string $dateTime, string $timezone)
    {
        try {
            $date = new \DateTime($dateTime, new \DateTimeZone($timezone));
            return $date->getTimestamp();
        } catch (\Exception $e) {
            Log::error('ZoomJWT->toUnixTimeStamp : ' . $e->getMessage());
            return null;
        }
    }
}
