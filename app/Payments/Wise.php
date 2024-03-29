<?php

namespace App\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Wise
{
    const URL = 'https://api.transferwise.com';
    const TOKEN = '80dd145a-94f6-41da-8b56-82b0af0cf229';
//    const URL_TEST = 'https://api.sandbox.transferwise.tech';
//    const TOKEN_TEST = '68355c49-468e-4854-adc1-99d7968329c6';

    public static function getTransfer($id)
    {
        $url = sprintf('%s/v1/transfers/%d', static::URL, $id);
        return static::get($url);
    }

    public static function getProfile($id)
    {
        $url = sprintf('%s/v2/profiles/%d', static::URL, $id);
        return static::get($url);
    }

    public static function getAccount($id)
    {
        $url = sprintf('%s/v2/accounts/%d', static::URL, $id);
        return static::get($url);
    }

    protected static function get($url)
    {
        try {
            $response = (new Client)->get($url, ['headers' => ['Authorization' => 'Bearer ' . static::TOKEN],]);
            $json = json_decode($response->getBody());
        } catch (ClientException $e) {
            $json = $e->getResponse()->getBody();
        }
        return $json;
    }
}
