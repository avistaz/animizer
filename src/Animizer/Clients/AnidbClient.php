<?php

namespace Animizer\Clients;

use Animizer\Data\Anime;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SimpleXMLElement;

class AnidbClient extends Client
{
    protected $apiUrl = 'api.anidb.net:9001/httpapi?request=anime&client=##APIKEY##&clientver=1&protover=1&aid=';

    protected $imageUrl = 'img7.anidb.net/pics/anime/';

    protected $apiSecure = false;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        parent::__construct();

        $this->imageUrl = ($this->apiSecure ? 'https://' : 'http://') . $this->imageUrl;
    }

    /**
     * @param $aid
     * @param null $xml_file
     * @return Anime
     * @throws \Exception
     */
    public function get($aid, $xml_file = null)
    {
        if ($xml_file) {
            $data = file_get_contents($xml_file);
            $data = str_replace("`", "'", $data);
            $xml = $this->parseXml($data);
        } else {
            $data = $this->request($this->apiUrl . $aid);
            $xml = $this->parseXml($data);
        }

        if (stripos($data, '<error') !== false) {
            throw new \Exception('AniDB Returned Error: ' . ((string)$xml->attributes()->code) . ': ' . ((string)$xml));
        }

        $anime = [];

        $anime['id'] = (string)$xml->attributes()->id;
        $anime['adult'] = (string)$xml->attributes()->restricted;
        if ($anime['adult'] == "true") {
            $anime['adult'] = true;
        } else {
            $anime['adult'] = false;
        }

        $anime['type'] = (string)$xml->type;
        $anime['language'] = $this->parseTitles($xml, true);
        $anime['titles'] = $this->parseTitles($xml);
        $anime['title'] = ($anime_title = $anime['titles']->where('type',
            'main')->first()) ? $anime_title['title'] : '';

        $anime['titles'] = $this->cleanTitles($anime['title'], $anime['titles']);

        $anime['start_date'] = (string)$xml->startdate;
        $anime['end_date'] = (string)$xml->enddate;

        if ((new Carbon($anime['start_date']))->greaterThan(new Carbon($anime['end_date']))) {
            $anime['end_date'] = null;
        }

        $anime['poster'] = $this->imageUrl . (string)$xml->picture;
        $anime['website'] = (string)$xml->url;
        $anime['creators'] = $this->parseCreators($xml);
        $anime['plot'] = $this->sanitizePlot((string)$xml->description);
        $anime['tags'] = $this->parseTags($xml);
        $anime['characters'] = $this->parseCharacters($xml);
        $anime['episode_count'] = (int)$xml->episodecount;
        $anime['episodes'] = $this->parseEpisodes($xml);
        $anime['franchise'] = $this->parseRelated($xml);

        return new Anime(collect($anime));
    }

    private function parseTitles(SimpleXMLElement $xml, $main = false)
    {
        $titles = [];
        if (!empty($xml->titles->title)) {

            $languages = $xml->xpath('titles/title[@xml:lang]/@xml:lang');
            $i = 0;
            foreach ($xml->titles->title as $title) {
                $titles[$i]['type'] = (string)$title->attributes()->type;

                $titles[$i]['title'] = (string)$title;
                $i++;
            }

            foreach ($languages as $index => $language) {
                if (isset($titles[$index])) {
                    $titles[$index]['language'] = (string)$language;
                }
            }
        }

        $titles = collect($titles);

        $main_language = $titles->where('type', 'main')->first();
        if ($main_language) {
            $main_language = $main_language['language'];
            if (Str::startsWith($main_language, 'x-')) {
                $main_language = Str::replaceFirst('x-', '', $main_language);
                $main_language = Str::replaceLast('t', '', $main_language);
            }
        }

        if ($main) {
            return $main_language;
        }

        $new_titles = [];
        foreach ($titles as $key => $title) {
            $new_titles[$key] = $title;

            if ($title['type'] == 'official' && ($title['language'] == $main_language || $title['language'] == 'en')) {
                $new_titles[$key]['type'] = 'main';
            }

            if (Str::startsWith($title['language'], 'x-')) {
                $new_titles[$key]['language'] = 'en';
            }

            if (Str::startsWith($title['language'], 'zh-')) {
                $new_titles[$key]['language'] = 'zh';
            }

            if ($new_titles[$key]['type'] != 'main') {
                $new_titles[$key]['type'] = 'alt';
            }
        }

        return collect($new_titles);
    }

    private function parseCreators(SimpleXMLElement $xml)
    {
        $creators = [];
        if (!empty($xml->creators->name)) {
            $i = 0;
            foreach ($xml->creators->name as $creator) {
                $creators[$i]['id'] = (string)$creator->attributes()->id;
                $creators[$i]['type'] = (string)$creator->attributes()->type;

                $creators[$i]['name'] = (string)$creator;
                $i++;
            }
        }

        return collect($creators);
    }

    private function parseTags(SimpleXMLElement $xml)
    {
        $tags = [];
        if (!empty($xml->tags->tag)) {
            $i = 0;
            foreach ($xml->tags->tag as $tag) {
                $tags[$i]['id'] = (string)$tag->attributes()->id;
                $tags[$i]['weight'] = (string)$tag->attributes()->weight;
                $tags[$i]['parent_id'] = (string)$tag->attributes()->parentid;
                $tags[$i]['verified'] = (string)$tag->attributes()->verified;

                if ($tags[$i]['verified'] == "true") {
                    $tags[$i]['verified'] = true;
                } else {
                    $tags[$i]['verified'] = false;
                }

                $tags[$i]['tag'] = (string)$tag->name;
                $tags[$i]['description'] = $this->sanitizePlot((string)$tag->description);
                $i++;
            }
        }

        $tags = collect($tags)->sortBy('weight', SORT_REGULAR, true);

        $new_tags = [];
        $key = 0;
        foreach ($tags as $tag) {
            if (
                !empty($tag['parent_id']) &&
                $tag['verified'] &&
                !in_array($tag['parent_id'],
                    ['30', '55', '2605', '2609', '2610', '2612', '2613', '2630', '2834', '6151', '6173']) &&
                !str_contains($tag['tag'], '--') && 
                $tag['weight'] >= 100
            ) {
                $new_tags[$key]['tag'] = $tag['tag'];
                $new_tags[$key]['description'] = $tag['description'];
                $key++;
            }
        }

        return collect($new_tags);
    }

    private function parseCharacters(SimpleXMLElement $xml)
    {
        $characters = [];
        if (!empty($xml->characters->character)) {
            $i = 0;
            foreach ($xml->characters->character as $character) {
                $characters[$i]['id'] = (string)$character->attributes()->id;
                $characters[$i]['type'] = (string)$character->attributes()->type;

                $characters[$i]['name'] = (string)$character->name;
                $characters[$i]['gender'] = (string)$character->gender;
                $characters[$i]['description'] = $this->sanitizePlot((string)$character->description);
                $characters[$i]['picture'] = $this->imageUrl . (string)$character->picture;
                $characters[$i]['actor'] = '';
                $characters[$i]['actor_id'] = '';
                $characters[$i]['actor_picture'] = '';
                if (isset($character->seiyuu)) {
                    $seiyuu = $character->seiyuu;
                    $characters[$i]['actor'] = (string)$seiyuu;
                    $characters[$i]['actor_id'] = (string)$seiyuu->attributes()->id;
                    $characters[$i]['actor_picture'] = $this->imageUrl . (string)$seiyuu->attributes()->picture;
                }

                $i++;
            }
        }

        return collect($characters);
    }

    private function parseEpisodes(SimpleXMLElement $xml)
    {
        $episodes = [];
        if (!empty($xml->episodes->episode)) {
            $i = 0;
            foreach ($xml->episodes->episode as $episode) {
                $episode_type = (int)((string)$episode->epno->attributes()->type);
                if ($episode_type == 1) {
                    $episodes[$i]['id'] = (string)$episode->attributes()->id;
                    $episodes[$i]['episode'] = (string)$episode->epno;
                    $episodes[$i]['title'] = (string)array_first($xml->xpath("episodes/episode[@id='" . $episodes[$i]['id'] . "']/title[@xml:lang='en']"));
                    $episodes[$i]['titles']['ja'] = (string)array_first($xml->xpath("episodes/episode[@id='" . $episodes[$i]['id'] . "']/title[@xml:lang='ja']"));
                    $episodes[$i]['titles']['x-jat'] = (string)array_first($xml->xpath("episodes/episode[@id='" . $episodes[$i]['id'] . "']/title[@xml:lang='x-jat']"));

                    if (empty($episodes[$i]['title'])) {
                        $episodes[$i]['title'] = $episodes[$i]['titles']['x-jat'];
                        if (empty($episodes[$i]['title'])) {
                            $episodes[$i]['title'] = $episodes[$i]['titles']['x-ja'];
                        }
                    }

                    $episodes[$i]['length'] = (string)$episode->length;
                    $episodes[$i]['summary'] = (string)$episode->summary;
                    $episodes[$i]['airdate'] = (string)$episode->airdate;
                }
                $i++;
            }
        }

        return collect($episodes)->sortBy('episode');
    }

    private function parseRelated($xml)
    {
        $related = [];
        if (!empty($xml->relatedanime->anime)) {
            $i = 0;
            foreach ($xml->relatedanime->anime as $anime) {
                $related[$i]['id'] = (string)$anime->attributes()->id;
                $related[$i]['type'] = (string)$anime->attributes()->type;
                $related[$i]['title'] = (string)$anime;
                $i++;
            }
        }

        return collect($related);
    }

    private function sanitizePlot($plot)
    {
        $patterns = [
            '/Source\:?\s[A-Za-z]{1,30}/i',
            '/http:\/\/anidb\.net\/[a-z]{1,2}[0-9]{1,20}\s/i',
        ];

        $plot = preg_replace($patterns, '', $plot);
        $plot = preg_replace("/[\r\n]+/", "\n", $plot);
        $plot = preg_replace('/\[(.+)\]/iU', '${1}', $plot);

        preg_match('/\*\s(Based\son.+)\n/iU', $plot, $based_on);
        if (isset($based_on[1])) {
            $plot = preg_replace('/\*\s(Based\son.+)\n/iU', '', $plot);
            $plot = $plot . "\n* " . $based_on[1];
        }

        return $plot;
    }

    private function cleanTitles($main_title, Collection $alt_titles)
    {
        $titles = [];
        foreach ($alt_titles as $title) {
            similar_text($main_title, $title['title'], $similarity);
            if ($similarity < 95) {
                $titles[] = $title;
            }
        }

        return collect($titles);
    }
}
