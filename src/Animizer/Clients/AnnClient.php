<?php

namespace Animizer\Clients;

use Animizer\Data\Anime;
use SimpleXMLElement;

class AnnClient extends Client
{
    protected $apiUrl = 'cdn.animenewsnetwork.com/encyclopedia/api.xml';

    protected $imageUrl = 'www.animenewsnetwork.com/images/encyc/';

    protected $apiSecure = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function get($aid)
    {
        $this->apiUrl = $this->apiUrl . '?title=';
        $data = $this->request($this->apiUrl . $aid);
        $xml = $this->parseXml($data);

        if ($xml->anime->count()) {
            $xml = $xml->anime;
        } elseif ($xml->manga->count()) {
            $xml = $xml->manga;
        }

        dump($xml);

        $anime = [];
        foreach ($xml->attributes() as $attribute_key => $attribute_value) {
            switch ($attribute_key) {
                case  'id':
                    $anime['ann_id'] = (string) $attribute_value;
                    break;
                case 'type':
                    $anime['type'] = (string) $attribute_value;
                    break;
                case 'name':
                    $anime['title'] = (string) $attribute_value;
                    break;
            }
        }

        $anime['titles'] = $this->parseTitles($xml);
        $anime['start_date'] = '';
        $anime['end_date'] = '';
        $anime['poster'] = '';
        $anime['website'] = '';
        $anime['creators'] = '';
        $anime['plot'] = '';
        $anime['genres'] = '';
        $anime['tags'] = '';
        $anime['characters'] = '';
        $anime['episodes'] = '';

        dump($anime);

        return new Anime(collect($anime));
    }

    private function parseTitles(SimpleXMLElement $xml)
    {
        $xmain_title = $xml->xpath("info[@type='Main title']");
        $xalt_titles = $xml->xpath("info[@type='Alternative title']");
        $xtitles = array_merge($xmain_title, $xalt_titles);

        $titles = [];
        if (!empty($xtitles)) {
            $i = 0;
            foreach ($xtitles as $xtitle) {
                foreach ($xtitle->attributes() as $key => $value) {
                    if ($key == 'type') {
                        $titles[$i]['type'] = (string) $value;
                    } elseif ($key == 'lang') {
                        $titles[$i]['language'] = (string) $value;
                    }
                }

                $titles[$i]['title'] = (string) $xtitle;
                $i++;
            }
        }

        return collect($titles);
    }
}
