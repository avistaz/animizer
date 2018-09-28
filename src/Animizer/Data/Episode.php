<?php

namespace Animizer\Data;

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
    public $air_date;

    /**
     * @var string
     */
    public $summary;

    /**
     * @var string [minutes]
     */
    public $runtime;

    /**
     * @var string
     */
    public $photo;

    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}