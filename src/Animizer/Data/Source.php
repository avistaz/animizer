<?php

namespace Animizer\Data;

class Source extends Base
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var string
     */
    public $url;

    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
