<?php

namespace Animizer\Data;

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
    public $name_native;

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

    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}