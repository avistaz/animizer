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

    public function get($aid, $xml_file = null)
    {
        if ($xml_file) {
            $data = file_get_contents($xml_file);
            $data = str_replace("`", "'", $data);
            $xml = $this->parseXml($data);
        } else {
            $this->apiUrl = $this->apiUrl . '?title=';
            $data = $this->request($this->apiUrl . $aid);
            $xml = $this->parseXml($data);
        }

        if ($xml->anime->count()) {
            $xml = $xml->anime;
        } elseif ($xml->manga->count()) {
            $xml = $xml->manga;
        }

        if (!empty($xml->warning)) {
            throw new \Exception('ANN Returned Error: ' . (string)$xml->warning);
        }

        $anime = [];
        foreach ($xml->attributes() as $attribute_key => $attribute_value) {
            switch ($attribute_key) {
                case  'id':
                    $anime['id'] = (string)$attribute_value;
                    break;
                case 'type':
                    $anime['type'] = (string)$attribute_value;
                    break;
                case 'name':
                    $anime['title'] = (string)$attribute_value;
                    break;
            }
        }

        $dates = $this->parseDates($xml);

        $content_rating = $this->getValue($xml, "info[@type='Objectionable content']");

        $anime['titles'] = $this->parseTitles($xml);
        $anime['start_date'] = $dates['start'];
        $anime['end_date'] = $dates['end'];
        $anime['runtime'] = $this->getValue($xml, "info[@type='Running time']");
        $anime['poster'] = $this->parsePoster($xml);
        $anime['website'] = '';
        $anime['creators'] = '';
        $anime['plot'] = $this->getValue($xml, "info[@type='Plot Summary']");
        $anime['genres'] = $this->getValues($xml, "info[@type='Genres']", 'genre');
        $anime['tags'] = $this->getValues($xml, "info[@type='Themes']", 'tag');
        $anime['characters'] = '';
        $anime['episodes'] = $this->getEpisodes($xml);
        $anime['episode_count'] = $anime['episodes']->count();
        $anime['creators'] = $this->getCreators($xml);

        /**
         * AA None
         * OC Mild (mild bad language and/or bloodless violence)
         * TA Significant (bloody violence and/or swearing and/or nudity)
         * MA Intense (extremely graphic depictions of sex, drug use, or bloodshed)
         * AO Pornographic
         */
        if($content_rating == 'AO') {
            $anime['adult'] = true;
        }

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
                $titles[$i]['type'] = $this->getAttribute($xtitle, 'type');
                $titles[$i]['language'] = $this->getAttribute($xtitle, 'lang');
                $titles[$i]['title'] = (string)$xtitle;
                $i++;
            }
        }

        return collect($titles);
    }

    private function parseDates(SimpleXMLElement $xml)
    {
        $start = null;
        $end = null;

        $date = $this->getValue($xml, "info[@type='Vintage']");
        if (!empty($date)) {
            preg_match_all('/(\d{4}-\d{2}-\d{2})/i', $date, $matches);
            if (isset($matches[1][0])) {
                $start = $matches[1][0];
            }
            if (isset($matches[1][1])) {
                $end = $matches[1][1];
            }
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function parsePoster(SimpleXMLElement $xml)
    {
        $posters = $xml->xpath("info[@type='Picture']");
        $image = '';
        if (!empty($posters)) {
            foreach ($posters as $poster) {
                $poster = $this->getAttribute($poster);
                if (isset($poster['src'])) {
                    $image = $poster['src'];
                }
            }
        }

        if (!empty($image)) {
            $image = explode('/', $image);
            $image = last($image);
            $image = ($this->apiSecure ? 'https://' : 'http://') . $this->imageUrl . $image;
        }

        return $image;
    }

    private function getEpisodes(SimpleXMLElement $xml)
    {
        $episodes = [];
        if (!empty($xml->episode)) {
            $counter = 0;
            foreach ($xml->episode as $episode) {
                $attributes = $this->getAttribute($episode);
                if ($attributes) {
                    $episodes[$counter]['episode'] = $attributes['num'];
                    $episodes[$counter]['title'] = (string)$episode->title[0];
                    $counter++;
                }
            }
        }

        return collect($episodes);
    }

    private function getCreators(SimpleXMLElement $xml)
    {
        foreach ($xml->staff as $staff) {
        }
    }

    private function getValues(SimpleXMLElement $xml, $xpath, $key)
    {
        $types = $xml->xpath($xpath);
        if (!empty($types)) {
            $data = [];
            foreach ($types as $type) {
                $data[][$key] = (string)$type;
            }
            return collect($data);
        }

        return null;
    }

    private function getValue(SimpleXMLElement $xml, $xpath)
    {
        $data = $xml->xpath($xpath);
        if ($data) {
            return (string)array_first($data);
        }
        return null;
    }

    /**
     * @param SimpleXMLElement $element
     * @param null $attribute
     * @return array|string
     */
    private function getAttribute(SimpleXMLElement $element, $attribute = null)
    {
        $data = [];
        foreach ($element->attributes() as $key => $value) {
            $data[$key] = (string)$value;
        }

        if ($attribute && isset($data[$attribute])) {
            return $data[$attribute];
        }

        return $data;
    }
}
