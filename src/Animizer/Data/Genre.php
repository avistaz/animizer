<?php

namespace Animizer\Data;

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

    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}