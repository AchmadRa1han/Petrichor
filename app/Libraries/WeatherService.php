<?php

namespace App\Libraries;

use Config\Services;

class WeatherService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct()
    {
        $this->apiKey = env('OPENWEATHER_API_KEY');
    }

    public function getWeatherByCoords($lat, $lon)
    {
        $client = Services::curlrequest();

        try {
            $response = $client->get($this->apiUrl, [
                'query' => [
                    'lat'   => $lat,
                    'lon'   => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric'
                ],
                'http_errors' => false // Biar kita bisa baca body kalau error
            ]);

            $status = $response->getStatusCode();
            $body   = json_decode($response->getBody(), true);

            if ($status !== 200) {
                return [
                    'error' => "Weather API Error ($status)",
                    'message' => $body['message'] ?? 'Unknown error'
                ];
            }

            return $body;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
