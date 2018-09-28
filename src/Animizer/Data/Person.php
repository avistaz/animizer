<?php

namespace Animizer\Data;

use Illuminate\Support\Collection;

class Person extends Base
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $aka;

    /**
     * @var string [male|female]
     */
    public $gender;

    /**
     * @var string
     */
    public $birthday;

    /**
     * @var string
     */
    public $deathday;

    /**
     * @var string
     */
    public $place_of_birth;

    /**
     * @var string
     */
    public $biography;

    /**
     * @var string
     */
    public $photo;

    public function __construct(Collection $data)
    {
        parent::__construct($data);
    }
}