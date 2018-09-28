<?php

namespace Animizer\Data;

use Illuminate\Support\Collection;

class Genre extends Base
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var int
     */
    public $genre;

    /**
     * @var string
     */
    public $description;

    /**
     * @var bool
     */
    public $adult;

    public function __construct(Collection $data)
    {
        parent::__construct($data);
    }
}