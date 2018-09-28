<?php

namespace Animizer\Data;

use Illuminate\Support\Collection;

class Episode extends Base
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var int
     */
    public $season;

    /**
     * @var int
     */
    public $episode;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $release_date;

    /**
     * @var string
     */
    public $plot;

    /**
     * @var string
     */
    public $runtime;

    /**
     * @var string
     */
    public $photo;

    public function __construct(Collection $data)
    {
        parent::__construct($data);
    }
}