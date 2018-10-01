<?php

namespace Animizer\Data;

class Staff extends Base
{
    /**
     * @var Job
     */
    public $job;

    /**
     * @var Person
     */
    public $person;

    public function __construct(array $data)
    {
        parent::__construct($data);

        if (!($this->job instanceof Job)) {
            $this->job = new Job((array)$this->job);
        }

        if (!($this->person instanceof Person)) {
            $this->person = new Person((array)$this->person);
        }
    }
}