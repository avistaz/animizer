<?php

namespace Animizer\Clients;

use GuzzleHttp\Client as GuzzleClient;
use SimpleXMLElement;

abstract class Client
{
    protected $apiUrl;

    protected $apiKey;

    protected $apiSecure = false;

    protected $cache = 86400;

    protected $guzzleOptions = [];

    public function __construct()
    {
        $this->buildApiUrl();
    }

    public function request($url)
    {
        $client = (new GuzzleClient());

        $response = $client->request('GET', $url, $this->guzzleOptions);

        $this->validateStatus($response->getStatusCode());

        return (string)$response->getBody();
    }

    public function toArray($string)
    {
        return json_decode($string, true);
    }

    public function toJson(array $array, $options = 0)
    {
        return json_encode($array, $options);
    }

    public function parseXml($body)
    {
        return new SimpleXMLElement($body);
    }

    private function validateStatus($statusCode)
    {
        if ($statusCode < 200 && $statusCode > 299) {
            throw new \HttpResponseException('Invalid Status Code');
        }
    }

    private function buildApiUrl()
    {
        $this->apiUrl = ($this->apiSecure ? 'https://' : 'http://') . $this->apiUrl;

        if (str_contains($this->apiUrl, '##APIKEY##')) {
            $this->apiUrl = str_replace('##APIKEY##', $this->apiKey, $this->apiUrl);
        }
    }
}