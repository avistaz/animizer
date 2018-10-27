<?php

namespace Animizer\Clients;

use Animizer\Data\Anime;
use Animizer\Data\Person;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SimpleXMLElement;

class AnidbClient extends Client
{
    public $apiUrl = 'https://api.anidb.net:9001/httpapi?request=anime&client=##APIKEY##&clientver=##APIVERSION##&protover=1&aid=';

    protected $imageUrl = 'https://img7.anidb.net/pics/anime/';

    public function __construct($api_key, $api_version = 1)
    {
        $this->apiKey = $api_key;

        parent::__construct();

        $this->apiUrl = str_replace('##APIVERSION##', $api_version, $this->apiUrl);
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

        $titles = $this->parseTitles($xml);
        $language = $this->guessLanguage($titles);
        list($title, $title_native, $title_romaji) = $this->guessMainTitles($titles, $language);

        $anime['type'] = (string)$xml->type;
        $anime['url'] = 'anidb.net/a' . $anime['id'];
        $anime['language'] = $language;
        $anime['title'] = $title;
        $anime['title_native'] = $title_native;
        $anime['title_romaji'] = $title_romaji;
        $anime['titles'] = $this->cleanupTitles([$title, $title_native, $title_romaji], $titles);
        $anime['start_date'] = (string)$xml->startdate;
        $anime['end_date'] = (string)$xml->enddate;

        if ((new Carbon($anime['start_date']))->greaterThan(new Carbon($anime['end_date']))) {
            $anime['end_date'] = null;
        }

        $anime['poster'] = $this->imageUrl . (string)$xml->picture;
        $anime['website'] = (string)$xml->url;
        $anime['staffs'] = $this->parseStaffs($xml);
        $anime['plot'] = $this->sanitizePlot((string)$xml->description);
        $anime['tags'] = $this->parseTags($xml);
        $anime['characters'] = $this->parseCharacters($xml);
        $anime['episode_count'] = (int)$xml->episodecount;
        $anime['episodes'] = $this->parseEpisodes($xml);
        $anime['franchise'] = $this->parseRelated($xml);

        return new Anime($anime);
    }

    private function parseTitles(SimpleXMLElement $xml)
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

        return collect($titles);
    }

    private function parseStaffs(SimpleXMLElement $xml)
    {
        $staffs = [];
        if (!empty($xml->creators->name)) {
            $i = 0;
            foreach ($xml->creators->name as $creator) {
                $staffs[$i]['job'] = (string)$creator->attributes()->type;
                $staffs[$i]['person'] = [
                    'id' => (string)$creator->attributes()->id,
                    'name' => (string)$creator,
                ];
                $i++;
            }
        }

        return $staffs;
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
                !in_array($tag['parent_id'], ['30', '55', '2605', '2609', '2610', '2612', '2613', '2630', '2834', '6151', '6173']) &&
                !str_contains($tag['tag'], '--') &&
                $tag['weight'] >= 100
            ) {
                $new_tags[$key]['id'] = $tag['id'];
                $new_tags[$key]['tag'] = $tag['tag'];
                $new_tags[$key]['description'] = $tag['description'];
                if (!in_array($tag['id'], ['7', '1760']) && in_array($tag['parent_id'], ['2608', '2848'])) {
                    $new_tags[$key]['adult'] = true;
                }
                $key++;
            }
        }

        return $new_tags;
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
                if (isset($character->seiyuu)) {
                    $seiyuu = $character->seiyuu;
                    $characters[$i]['actor'] = new Person([
                        'id' => (string)$seiyuu->attributes()->id,
                        'name' => (string)$seiyuu,
                        'photo' => $this->imageUrl . (string)$seiyuu->attributes()->picture,
                    ]);
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

                    $episodes[$i]['runtime'] = (string)$episode->length;
                    $episodes[$i]['summary'] = (string)$episode->summary;
                    $episodes[$i]['air_date'] = (string)$episode->airdate;
                }
                $i++;
            }
        }

        return collect($episodes)->sortBy('episode');
    }

    private function guessLanguage(Collection $titles)
    {
        $title = $titles->first();
        if (isset($title['language'])) {
            $language = $title['language'];
            if (Str::startsWith($language, 'x-') && Str::endsWith($language, 't')) {
                $language = Str::replaceFirst('x-', '', $language);
                $language = Str::replaceLast('t', '', $language);
            }
            return $language;
        }
        return null;
    }

    private function guessMainTitles(Collection $titles, $language)
    {
        $title = null;
        $title_native = null;
        $title_romaji = null;

        $english_title = $titles->where('language', 'en')->where('type', 'main')->first();
        if ($english_title) {
            $title = $english_title['title'];
        }
        if (empty($english_title)) {
            $english_title = $titles->where('language', 'en')->where('type', 'official')->first();
            if ($english_title) {
                $title = $english_title['title'];
            }
        }

        $romaji_title = $titles->where('language', 'x-' . $language . 't')->where('type', 'main')->first();
        if ($romaji_title && $romaji_title['title'] != $title) {
            $title_romaji = $romaji_title['title'];
        }
        if (empty($romaji_title)) {
            $romaji_title = $titles->where('language', 'x-' . $language . 't')->where('type', 'official')->first();
            if ($romaji_title && $romaji_title['title'] != $title) {
                $title_romaji = $romaji_title['title'];
            }
        }

        $native_title = $titles->where('language', $language)->where('type', 'main')->first();
        if ($native_title && $native_title['title'] != $title && $native_title['title'] != $romaji_title) {
            $title_native = $native_title['title'];
        }
        if (empty($native_title)) {
            $native_title = $titles->where('language', $language)->where('type', 'official')->first();
            if ($native_title && $native_title['title'] != $title && $native_title['title'] != $romaji_title) {
                $title_native = $native_title['title'];
            }
        }

        if (empty($title)) {
            $first_title = $titles->first();
            if ($first_title) {
                $title = $first_title['title'];
            }
        }

        return [
            $title,
            $title_native,
            $title_romaji,
        ];
    }

    private function parseRelated($xml)
    {
        $related = [];
        if (!empty($xml->relatedanime->anime)) {
            foreach ($xml->relatedanime->anime as $anime) {
                $related[] = [
                    'id' => (string)$anime->attributes()->id,
                    'type' => (string)$anime->attributes()->type,
                    'title' => (string)$anime,
                ];
            }
        }

        return $related;
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

        $titles = array_values($titles);

        $titles = collect($titles)->map(function ($title) {
            $title['type'] = 'alt';
            if (Str::startsWith($title['language'], 'x-')) {
                $title['language'] = 'en';
            }
            if (Str::startsWith($title['language'], 'zh-')) {
                $title['language'] = 'zh';
            }
            return $title;
        })->toArray();

        return $titles;
    }
}
