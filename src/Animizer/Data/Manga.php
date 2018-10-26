<?php

namespace Animizer\Data;

use Illuminate\Support\Collection;

class Manga extends Base
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
     * @var string Source URL
     */
    public $url;

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
    public $pages;

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
    public $volume_count;

    /**
     * @var Collection
     */
    public $volumes;

    /**
     * @var int
     */
    public $chapter_count;

    /**
     * @var Collection
     */
    public $chapters;

    /**
     * @var Collection [type={prequel, sequel, side-story, adaptation}, anidb_id, $ann_id]
     */
    public $franchise;

    /**
     * @var Collection
     */
    public $sources;
}