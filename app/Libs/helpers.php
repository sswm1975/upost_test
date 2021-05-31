<?php

function test_api_post($url, $credentials = '', $post_data = [])
{
    $curl = curl_init($url);

    if ($credentials) {
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $credentials);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $curl_response = curl_exec($curl);
    $response = json_decode($curl_response);
    curl_close($curl);

    return $response;
}

function test_api_get($url)
{
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $curl_response = curl_exec($curl);
    $response = json_decode($curl_response);
    curl_close($curl);

    return $response;
}

/**
 * Convert Null to Blank string.
 *
 * @param array $data
 * @return array
 */
function null_to_blank(array $data = []): array
{
    array_walk_recursive($data, function (&$item) {
        $item = $item === null ? '' : $item;
    });

    return $data;
}
