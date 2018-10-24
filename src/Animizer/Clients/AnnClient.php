<?php

namespace Animizer\Clients;

use Animizer\Data\Anime;
use Animizer\Data\Person;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SimpleXMLElement;

class AnnClient extends Client
{
    protected $apiUrl = 'http://cdn.animenewsnetwork.com/encyclopedia/api.xml';

    protected $imageUrl = 'http://www.animenewsnetwork.com/images/encyc/';

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

        $type = 'anime';
        if ($xml->anime->count()) {
            $xml = $xml->anime;
        } elseif ($xml->manga->count()) {
            $xml = $xml->manga;
            $type = 'manga';
        }

        if (!empty($xml->warning)) {
            throw new \Exception('ANN Returned Error: ' . (string)$xml->warning);
        }

        $dates = $this->parseDates($xml);
        $content_rating = $this->getValue($xml, "info[@type='Objectionable content']");
        $titles = $this->parseTitles($xml);
        list($title, $title_native, $title_romaji) = $this->guessMainTitles($titles);

        $anime = [];
        $anime['id'] = (string)$xml->attributes()->id;
        $anime['type'] = (string)$xml->attributes()->type;
        $anime['url'] = 'animenewsnetwork.com/encyclopedia/' . $type . '.php?id=' . $anime['id'];
        $anime['title'] = (string)$xml->attributes()->name;
        $anime['title_native'] = $title_native;
        $anime['title_romaji'] = $title_romaji;
        $anime['titles'] = $this->cleanupTitles([$anime['title'], $title_native, $title_romaji], $titles);
        $anime['language'] = 'ja';
        $anime['start_date'] = $dates['start'];
        $anime['end_date'] = $dates['end'];
        $anime['runtime'] = $this->getValue($xml, "info[@type='Running time']");
        $anime['poster'] = $this->parsePoster($xml);
        $anime['website'] = $this->getWebsite($xml);
        $anime['plot'] = $this->getValue($xml, "info[@type='Plot Summary']");
        $anime['genres'] = $this->getValues($xml, "info[@type='Genres']", 'genre');
        $anime['tags'] = $this->getValues($xml, "info[@type='Themes']", 'tag');
        $anime['characters'] = $this->getCharacters($xml);
        $anime['episodes'] = $this->getEpisodes($xml);
        $anime['episode_count'] = $anime['episodes']->count();
        $anime['staffs'] = $this->getStaffs($xml);
        $anime['franchise'] = $this->getFranchise($xml);

        if (!is_numeric($anime['runtime'])) {
            $anime['runtime'] = strtolower($anime['runtime']);
            if ($anime['runtime'] == 'half hour') {
                $anime['runtime'] = 30;
            } elseif ($anime['runtime'] == 'one hour') {
                $anime['runtime'] = 60;
            } else {
                $anime['runtime'] = null;
            }
        }

        if ($anime['type'] == 'manga') {
            $precision = (string)$xml->attributes()->precision;
            if (!empty($precision) && !Str::startsWith($precision, ['manga'])) {
                $anime['type'] = $precision;
            }
        }

        /**
         * AA None
         * OC Mild (mild bad language and/or bloodless violence)
         * TA Significant (bloody violence and/or swearing and/or nudity)
         * MA Intense (extremely graphic depictions of sex, drug use, or bloodshed)
         * AO Pornographic
         */
        if ($content_rating == 'AO') {
            $anime['adult'] = true;
        } else {
            $anime['adult'] = false;
        }

        return new Anime($anime);
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
                $titles[$i]['type'] = (string)$xtitle->attributes()->type;
                $titles[$i]['language'] = (string)$xtitle->attributes()->lang;
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
                $image = (string)$poster->attributes()->src;
            }
        }

        if (!empty($image)) {
            $image = explode('/', $image);
            $image = last($image);
            $image = $this->imageUrl . $image;
        }

        return $image;
    }

    private function getEpisodes(SimpleXMLElement $xml)
    {
        $episodes = [];
        if (!empty($xml->episode)) {
            $counter = 0;
            foreach ($xml->episode as $episode) {
                $episodes[$counter]['episode'] = (string)$episode->attributes()->num;
                $episodes[$counter]['title'] = (string)$episode->title;
                $counter++;
            }
        }

        return collect($episodes);
    }

    private function getCharacters(SimpleXMLElement $xml)
    {
        $characters = [];
        foreach ($xml->xpath("cast[@lang='JA']") as $cast) {
            $characters[] = [
                'name' => (string)$cast->role,
                'actor' => new Person([
                    'id' => (string)$cast->person->attributes()->id,
                    'name' => (string)$cast->person,
                ]),
            ];
        }

        return $characters;
    }

    private function getStaffs(SimpleXMLElement $xml)
    {
        $staffs = [];
        foreach ($xml->staff as $staff) {
            $staffs[] = [
                'job' => (string)$staff->task,
                'person' => new Person([
                    'id' => (string)$staff->person->attributes()->id,
                    'name' => (string)$staff->person,
                ]),
            ];
        }

        return $staffs;
    }

    private function getWebsite(SimpleXMLElement $xml)
    {
        $websites = $xml->xpath("info[@type='Official website']");
        $websites = $this->collectAttributes($websites);

        if ($website = $websites->where('lang', 'EN')->first()) {
            return $website['href'] ?? null;
        }
        if ($website = $websites->where('lang', 'JA')->first()) {
            return $website['href'] ?? null;
        }
        if ($website = $websites->first()) {
            return $website['href'] ?? null;
        }

        return null;
    }

    private function getFranchise(SimpleXMLElement $xml)
    {
        $related_prev = $xml->xpath("related-prev");
        $related_next = $xml->xpath("related-next");
        $related_all = array_merge($related_prev, $related_next);

        $franchise = [];
        foreach ($related_all as $related) {
            $relation = (string)$related['rel'];
            if ($relation == 'adapted from') {
                $relation = 'adaptation';
            }
            if ($relation == 'sequel of') {
                $relation = 'prequel';
            }
            if ($relation == 'prequel of') {
                $relation = 'sequel';
            }
            if ($relation == 'side story of') {
                $relation = 'side-story';
            }
            if ($relation == 'spinoff of') {
                $relation = 'spin-off';
            }
            $franchise[] = [
                'id' => (string)$related['id'],
                'type' => $relation,
            ];
        }

        return $franchise;
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

    private function collectAttributes($elements)
    {
        $data = [];
        foreach ($elements as $master => $element) {
            foreach ($element->attributes() as $key => $value) {
                $data[$master][$key] = (string)$value;
            }
        }

        return collect($data);
    }

    private function guessMainTitles(Collection $titles)
    {
        $title = null;
        $title_native = null;
        $title_romaji = null;

        $english_title = $titles->where('language', 'EN')->first();
        if ($english_title) {
            $title = $english_title['title'];
        }

        $romaji_title = $titles->where('language', 'EN');
        if (isset($romaji_title[1])) {
            $title_romaji = $romaji_title[1]['title'];
        }

        $native_title = $titles->where('language', 'JA')->first();
        if ($native_title) {
            $title_native = $native_title['title'];
        }

        if (empty($title)) {
            $first_title = $titles->first();
            if ($first_title) {
                $title = $first_title['title'];
            }
        }
        if (empty($title_romaji)) {
            $title_romaji = $title;
        }

        return [
            $title,
            $title_native,
            $title_romaji,
        ];
    }

    private function cleanupTitles($main_titles, $alt_titles)
    {
        $titles = [];
        foreach ($main_titles as $main_title) {
            foreach ($alt_titles as $title) {
                if (!in_array($title['title'], $main_titles)) {
                    if (levenshtein($main_title, $title['title']) > 3) {
                        $titles[md5($title['title'])] = $title;
                    }
                }
            }
        }

        return array_values($titles);
    }
}
