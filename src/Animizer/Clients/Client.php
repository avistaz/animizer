<?php

namespace Animizer\Clients;

use Animizer\Services\Guzzler;
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
        $response = (new Guzzler($this->guzzleOptions))->setUrl($url)->get();

        $this->validateStatus($response->getStatusCode());

        return $response->getBody();
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