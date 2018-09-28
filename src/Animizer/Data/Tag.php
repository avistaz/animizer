<?php

namespace Animizer\Data;

class Tag extends Base
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var string
     */
    public $tag;

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