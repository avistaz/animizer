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
    public $creators;

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

        $this->language = new Language([$this->language]);

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

        $this->creators = collect($this->creators);

        $this->sources = collect($this->sources)->map(function ($source) {
            return new Source($source);
        });
    }

    private function guessType()
    {
        if (!empty($this->type)) {
            $this->type = strtolower($this->type);

            if ($this->type == 'oav') {
                $this->type = 'ova';
            }

            if (in_array($this->type, ['tv series', 'tv special'])) {
                $this->type = 'tv';
            }

            if (in_array($this->type, ['movie', 'tv', 'manga', 'ova'])) {
                return $this->type;
            }

            return $this->type;
        }

        return null;
    }
}
