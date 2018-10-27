<?php

namespace Animizer\Clients;

use DOMDocument;
use GuzzleHttp\Client as GuzzleClient;

abstract class Client
{
    public $apiUrl;

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
        libxml_use_internal_errors(true);
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->strictErrorChecking = false;
        $dom->validateOnParse = false;
        $dom->recover = true;
        $dom->loadXML($body);
        $xml = simplexml_import_dom($dom);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return $xml;
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