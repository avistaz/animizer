<?php

namespace Animizer\Data;

use Illuminate\Support\Collection;

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

    public function __construct(Collection $data)
    {
        parent::__construct($data);
    }
}