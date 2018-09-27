<?php

namespace Animizer\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class Person implements Arrayable, Jsonable
{
    public $id;

    public $name;

    public $aka;

    public $gender;

    public $birthday;

    public $deathday;

    public $placeOfBirth;

    public $biography;

    public $photo;

    public $photos;

    public $character;

    public $order;

    public $job;

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        // TODO: Implement toArray() method.
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        // TODO: Implement toJson() method.
    }
}