<?php

namespace Animizer\Data;

use Illuminate\Support\Collection;

class Anime extends Base
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var string [tv, movie, ova, tv-special, music-video, web, unknown]
     */
    public $type;

    /**
     * @var Language
     */
    public $language;

    /**
     * @var bool
     */
    public $adult;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $title_native;

    /**
     * @var
     */
    public $title_romaji;

    /**
     * @var Collection
     */
    public $titles;

    /**
     * @var string
     */
    public $start_date;

    /**
     * @var string
     */
    public $end_date;

    /**
     * @var string
     */
    public $runtime;

    /**
     * @var string
     */
    public $poster;

    /**
     * @var string
     */
    public $website;

    /**
     * @var Collection
     */
    public $staffs;

    /**
     * @var string
     */
    public $plot;

    /**
     * @var Collection
     */
    public $genres;

    /**
     * @var Collection
     */
    public $tags;

    /**
     * @var Collection
     */
    public $characters;

    /**
     * @var int
     */
    public $episode_count;

    /**
     * @var Collection
     */
    public $episodes;

    /**
     * @var Collection [type={prequel, sequel, side-story, adaptation}, anidb_id, $ann_id]
     */
    public $franchise;

    /**
     * @var Collection
     */
    public $sources;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->type = $this->guessType();

        if (!($this->language instanceof Language)) {
            $this->language = new Language((array)$this->language);
        }

        $this->tags = collect($this->tags)->map(function ($tag) {
            return new Tag($tag);
        });

        $this->genres = collect($this->genres)->map(function ($genre) {
            return new Genre($genre);
        });

        $this->titles = collect($this->titles)->map(function ($title) {
            return new Title($title);
        });

        $this->episodes = collect($this->episodes)->map(function ($episode) {
            return new Episode($episode);
        });

        $this->characters = collect($this->characters)->map(function ($character) {
            return new Character($character);
        });

        $this->franchise = collect($this->franchise)->map(function ($franchise) {
            return new Franchise($franchise);
        });

        $this->staffs = collect($this->staffs)->map(function ($staff) {
            return new Staff($staff);
        });

        $this->sources = collect($this->sources)->map(function ($source) {
            return new Source($source);
        });
    }

    private function guessType()
    {
        $types = collect([
            [
                'category' => 'TV Series',
                'slug' => 'tv.series',
            ],
            [
                'category' => 'Movie',
                'slug' => 'movie',
            ],
            [
                'category' => 'OVA',
                'slug' => 'ova',
            ],
            [
                'category' => 'OAD',
                'slug' => 'oad',
            ],
            [
                'category' => 'TV Special',
                'slug' => 'tv.special',
            ],
            [
                'category' => 'BD/DVD Special',
                'slug' => 'bd.dvd.special',
            ],
            [
                'category' => 'Web/ONA',
                'slug' => 'web.ona',
            ],
            [
                'category' => 'Aeni',
                'slug' => 'aeni',
            ],
            [
                'category' => 'Donghua',
                'slug' => 'donghua',
            ],
            [
                'category' => 'Doujin Anime',
                'slug' => 'doujin.anime',
            ],
            [
                'category' => 'Music Video',
                'slug' => 'music.video',
            ],
            [
                'category' => 'Other',
                'slug' => 'other',
            ],
            [
                'category' => 'Manga',
                'slug' => 'manga',
            ],
            [
                'category' => 'Artbook',
                'slug' => 'artbook',
            ],
            [
                'category' => 'Light Novel',
                'slug' => 'light.novel',
            ],
            [
                'category' => 'Manhwa',
                'slug' => 'manhwa',
            ],
            [
                'category' => 'Manhua',
                'slug' => 'manhua',
            ],
            [
                'category' => 'Doujinshi',
                'slug' => 'doujinshi',
            ],
            [
                'category' => 'One-Shot',
                'slug' => 'one.shot',
            ],
            [
                'category' => 'OST',
                'slug' => 'ost',
            ],
            [
                'category' => 'Album',
                'slug' => 'album',
            ],
            [
                'category' => 'Single',
                'slug' => 'single',
            ],
            [
                'category' => 'Drama',
                'slug' => 'drama',
            ],
            [
                'category' => 'Compilation',
                'slug' => 'compilation',
            ],
        ]);
        $type = $this->type;

        if (!empty($type)) {
            $type = strtolower($this->type);
            $type = str_replace([' ', '/', '.'], '.', $type);

            switch ($type) {
                case 'oav':
                    $type = 'ova';
                    break;
                case 'tv':
                    $type = 'tv.series';
                    break;
                case 'web':
                case 'ona':
                    $type = 'web.ona';
                    break;
                case 'omnibus':
                    $type = 'manga';
                    break;

                default:
                    break;
            }
        }

        return $type;
    }
}
