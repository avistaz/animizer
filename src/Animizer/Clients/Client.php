<?php

namespace Animizer\Clients;

use GuzzleHttp\Client as GuzzleClient;
use SimpleXMLElement;

abstract class Client
{
    protected $apiUrl;

    protected $apiKey;

    protected $cache = 86400;

    /**
     * @var GuzzleClient
     */
    protected $guzzleClient;

    protected $guzzleOptions = [];

    public function __construct()
    {
        $this->buildApiUrl();

        $this->guzzleClient = (new GuzzleClient());
    }

    public function request($url)
    {
        $response = $this->guzzleClient->request('GET', $url, $this->guzzleOptions);

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
            throw new \HttpResponseException('Error! Client returned code: ' . $statusCode);
        }
    }

    private function buildApiUrl()
    {
        if (str_contains($this->apiUrl, '##APIKEY##')) {
            $this->apiUrl = str_replace('##APIKEY##', $this->apiKey, $this->apiUrl);
        }
    }
}