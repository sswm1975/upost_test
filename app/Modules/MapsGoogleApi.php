<?php

namespace App\Modules;

use Exception;
use GuzzleHttp\Client;

class MapsGoogleApi
{
    public static function getCitiNameInLanguage(string $city_en = null, string $country = null, string $language = 'en')
    {
        if (empty($city_en) || empty($country)) return null;

        $url = sprintf('https://maps.googleapis.com/maps/api/geocode/json?address=%s,%s&sensor=false&language=%s&key=%s',
            $city_en,
            $country,
            $language,
            config('maps_google_api_key', '')
        );

        try {
            $response = (new Client)->get($url);
            $json = json_decode($response->getBody());
            $city = $json->results[0]->address_components[0]->long_name ?? null;
        } catch (Exception $e) {
            $city = null;
        }

        return $city;
    }
}


